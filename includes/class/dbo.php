<?php

/**
 *
 * @author	Ahmet Şenocak
 */
class dbo {

	public	$link			= null;

	public	$table_prefix	= '';

	/**
	 *
	 *
	 */
	public static function connect($a) {
		$o			= new dbo();
		//$o->link	= mysqli_connect($a['host'], $a['user'], $a['password'], $a['name'], $a['port']);
		$o->link	= mysqli_connect("127.0.0.1", "root", "", "crawler","3306");
		if (array_key_exists('charset', $a)) {
			$o->link->set_charset($a['charset']);
		}
		if (array_key_exists('table_prefix', $a)) {
			$o->table_prefix	= $a['table_prefix'];
		}
		return $o;
	}

	/**
	 *
	 *
	 */
	public function begin() {
		return $this->execute('BEGIN');
	}

	/**
	 *
	 *
	 */
	public function start_transaction() {
		return $this->execute('START TRANSACTION');
	}

	/**
	 *
	 *
	 */
	public function commit() {
		return $this->execute('COMMIT');
	}

	/**
	 *
	 *
	 */
	public function rollback() {
		return $this->execute('ROLLBACK');
	}

	/**
	 *
	 *
	 */
	public function execute($sql) {
		//echo $sql."\n";
		$result	= $this->link->query($sql);
		if ($result === false) {
			$e	= new exception('DATABASE_ERROR');
			$e->sql		= $sql;
			$e->error	= $this->link->error;
			echo $sql."\n\n";
			throw $e;
		}

		if (gettype($result) == 'object') {
			$ret	= array();
			while ($row = $result->fetch_assoc()) {
				$data	= array();
				for ($k = 0; $k < $result->field_count; $k ++) {
					$field	= $result->fetch_field_direct($k);
					$value	= $row[$field->name];
					if (!is_null($value)) {
						switch ($field->type) {
							// Sayı tipleri
							case 0: case 1: case 2: case 3: case 899: case 9:
								$value	= (int) $value;

							break;
							// Ondalık sayı tipleri
							case 4: case 5: case 8: case 246:
								$value	= floatval( $value );

							break;
							// Tarih ve saat (timestamp)
							case 7:
								$splitted	= preg_split('/[\D]/', $value);
								$value		= mktime($splitted[3],$splitted[4],$splitted[5], $splitted[1], $splitted[2], $splitted[0]);
							break;
							// Tarih (date)
							case 10:
								$splitted	= preg_split('/[\D]/', $value);
								$value		= mktime(0, 0, 0, $splitted[1], $splitted[2], $splitted[0]);
							break;
							// Tarih ve saat (datetime)
							case 12:
								$splitted	= preg_split('/[\D]/', $value);
								$value		= mktime($splitted[3],$splitted[4],$splitted[5], $splitted[1], $splitted[2], $splitted[0]);

							break;
							// Yıl
							case 13:
								$value		= mktime(0, 0, 0, 1, 1, $value);

							break;
							// Enum
							case 247:

							break;
							// Set
							case 254:
								#if (strstr($value, ','))
								#	$value	= explode(',', $value);

							break;
							// Metinsel tipler
							case 249: case 250: case 251: case 252: case 253: case 255:
								$value	= (string) $value;

							break;
						}
					}
					$data[strtolower($field->name)]	= $value;
				}
				$ret[]	= $data;
			}
			$result->close();
			return $ret;
		} else {
			//$affected_rows	= $this->link->affected_rows;
			return $this->link->insert_id;
		}
		return true;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function select($table_name, $a=array()) {
		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');

		if (!$table_name)
			return false;

		$sql	= 'SELECT ';
		if (isset($a['fields'])) {
			if (is_array($a['fields']) and sizeof($a['fields'])) {
				foreach ($a['fields'] as $field) {
					$sql	.= '`'.escaper::escape(str_replace('`', '', $field), 'bs:e').'`,';
				}
				$sql	= substr($sql, 0, -1);
			} elseif (strlen($a['fields'])) {
				$sql	.= $a['fields'];
			}
		} else {
			$sql	.= '*';
		}
		$sql	.= ' FROM `'.$table_name.'`';

		if (isset($a['where'])) {
			$a['where']	= $this->where_sql($a['where']);
			if (strlen($a['where']))
				$sql	.= ' WHERE '.$a['where'];
		}
		if (isset($a['order_by']) and strlen($a['order_by'])) {
			$sql	.= ' ORDER BY `'.escaper::escape(str_replace('`', '', $a['order_by']), 'bs:e').'`';
		}
		if (isset($a['order_direction']) and in_array(strtoupper($a['order_direction']), array('ASC', 'DESC'))) {
			$sql	.= ' '.$a['order_direction'];
		}
		if (isset($a['limit']) and strlen($a['limit'])) {
			$sql	.= ' LIMIT '.intval($a['limit']);
		}
		if (isset($a['offset']) and strlen($a['offset'])) {
			$sql	.= ' OFFSET '.intval($a['offset']);
		}
		return $this->execute($sql);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function seek($table_name, $where=null, $fields='*') {
		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');

		if (!$table_name)
			return false;

		$a	= array('fields'=>$fields,'limit'=>1, 'where'=>$where);

		$result	= $this->select($table_name, $a);
		if (!sizeof($result))
			return false;

		return $result[0];
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function exists($table_name, $where=null) {
		if ($this->count($table_name, $where))
			return true;
		return false;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function max($table_name, $field_name, $where=null) {
		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');
		$field_name	= escaper::escape($field_name, 'bs:e;sq:e');

		if (!$table_name or !$field_name)
			return false;

		$sql	= 'SELECT MAX(`'.$field_name.'`) as `max` FROM `'.$table_name.'`';

		$where	= $this->where_sql($where);
		if (strlen($where))
			$sql	.= ' WHERE '.$where;

		$result	= $this->execute($sql);
		if (isset($result[0]->max))
			return $result[0]->max;
		return 0;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function count($table_name, $where=null) {
		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');

		if (!$table_name)
			return false;

		$sql	= 'SELECT count(*) as `count` FROM `'.$table_name.'`';

		$where	= $this->where_sql($where);
		if (strlen($where))
			$sql	.= ' WHERE '.$where;

		$result	= $this->execute($sql);
		if (isset($result[0]['count']))
			return $result[0]['count'];
		return 0;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function insert($table_name, $values=array(), $on_duplicate_key_update=false) {

		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');

		if (!$table_name)
			return false;

		$sql	= 'INSERT INTO `'.$table_name.'` (';
		$vsql	= ' VALUES (';
		$usql	= '';

		if (sizeof($values)) {
			foreach ($values as $key => $val) {
				$key	= escaper::escape(str_replace('`', '', $key), 'bs:e');
				$sql	.= '`'.$key.'`,';

				if (is_null($val)) {
					$vsql	.= 'NULL,';
				} elseif (is_bool($val)) {
					$vsql	.= ($val ? '1':'0').',';
				} else {
					$val	= escaper::escape($val, 'bs:e;sq:e');
					$vsql	.= '\''.$val.'\',';
				}
			}
			$sql	= substr($sql, 0, -1);
			$vsql	= substr($vsql, 0, -1);
		}

		$sql	.= ')'.$vsql.')';

		if ($on_duplicate_key_update) {
			$sql	.= ' ON DUPLICATE KEY UPDATE ';

			// Hash gönderilmişse, güncellenecek alanlar ve değerleri gönderilmiş.
			if (is_real_hash($on_duplicate_key_update)) {
				foreach ($on_duplicate_key_update as $key => $val) {
					$key	= escaper::escape(str_replace('`', '', $key), 'bs:e');
					$sql	.= '`'.$key.'`=';
					if (is_null($val)) {
						$sql	.= 'NULL,';
					} elseif (is_bool($val)) {
						$sql	.= ($val ? '1':'0').',';
					} else {
						$val	= escaper::escape($val, 'bs:e;sq:e');
						$sql	.= '\''.$val.'\',';
					}
				}

			// Sayısal dizi gönderilmişse, güncellenecek alan isimleri gönderilmiş.
			} elseif (is_real_array($on_duplicate_key_update)) {
				foreach ($on_duplicate_key_update as $key) {
					if (!array_key_exists($key, $values))
						continue;
					$key	= escaper::escape(str_replace('`', '', $key), 'bs:e');
					$sql	.= '`'.$key.'`=VALUES(`'.$key.'`),';
				}

			// true gönderilmişse tüm alanları ekle.
			} else {
				$keys	= array_keys($values);
				foreach ($keys as $key) {
					$key	= escaper::escape(str_replace('`', '', $key), 'bs:e');
					$sql	.= '`'.$key.'`=VALUES(`'.$key.'`),';
				}
			}

			$sql	= substr($sql, 0, -1);
		}

		return $this->execute($sql);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function update($table_name, $values=array(), $where='', $lock=false) {

		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');

		if (!$table_name)
			return false;

		$sql	= 'UPDATE `'.$table_name.'` SET ';
		if (sizeof($values)) {
			foreach ($values as $key => $val) {
				$key	= escaper::escape(str_replace('`', '', $key), 'bs:e');
				$sql	.= '`'.$key.'`=';
				if (is_null($val)) {
					$sql	.= 'NULL,';
				} elseif (is_bool($val)) {
					$sql	.= ($val ? '1':'0').',';
				} else {
					$val	= escaper::escape($val, 'bs:e;sq:e');
					$sql	.= '\''.$val.'\',';
				}
			}
			$sql	= substr($sql, 0, -1);
		}

		$where	= $this->where_sql($where);
		if (strlen($where))
			$sql	.= ' WHERE '.$where;

		if ($lock) {
			$sql	.= ' LOCK IN SHARE MODE ';

			$t = $this->execute($sql);
			if($t){
				$this->commit();
				return $t;
			} else{
				$this->rollback();
			}
		}
		//echo $sql;
		return $this->execute($sql);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function delete($table_name, $where='') {
		if (!$where)
			return;

		$table_name	= escaper::escape($this->table_prefix.$table_name, 'bs:e;sq:e');

		if (!$table_name)
			return false;

		$sql	= 'DELETE FROM `'.$table_name.'` ';

		$where	= $this->where_sql($where);
		if (strlen($where))
			$sql	.= ' WHERE '.$where;
		return $this->execute($sql);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function where_sql($where) {
		switch (true) {
			case is_null($where):
				return '';
			break;
			case is_id($where):
				return '`id`='.$where;
			break;
			case is_string($where):
				return $where;
			break;
			case is_real_array($where):

			break;
			case is_real_hash($where):
				$str	= '';
				foreach ($where as $n => $v) {
					$str	.= '`'.escaper::escape($n, 'bs:e;sq:e').'`';
					if (is_null($v)) {
						$str	.= ' IS NULL';
					} elseif (is_bool($v)) {
						$str	.= '='. ($v ? '1':'0');
					} elseif (is_real_array($v)) {
						// Dizi boşsa sonuç gelmesini engelle.
						if (!sizeof($v)) {
							$str	.= ' IS NULL AND 1=0';
							continue;
						}

						$str	.= ' IN(';
						foreach ($v as $vi) {
							$str	.= '\''.escaper::escape($vi, 'bs:e;sq:e').'\',';
						}
						unset($vi);
						$str	= substr($str, 0, -1);
						$str	.= ')';
					} else {
						$str	.= '=\''.escaper::escape($v, 'bs:e;sq:e').'\'';
					}
					$str	.= ' AND ';
				}
				$str	= substr($str, 0, -5);
				return $str;
			break;
		}

	}


}
?>