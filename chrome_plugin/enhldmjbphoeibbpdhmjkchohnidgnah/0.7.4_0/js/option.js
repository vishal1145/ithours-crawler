/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};

/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {

/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;

/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};

/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;

/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}


/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;

/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;

/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";

/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';

	var Analytics = __webpack_require__(1);
	var Credentials = __webpack_require__(3);
	var CredentialStorage = __webpack_require__(4);
	var Translator = __webpack_require__(5);

	var OptionPanel = function() {
	    var credentials = [];
	    var file_credentials = [];

	    var import_output;
	    var import_file_field;
	    var import_json_field;

	    var analytics_field;

	    function modal(title, content, confirmation) {
	        var overlay = document.getElementsByClassName('overlay')[0].cloneNode(true);

	        overlay.removeAttribute('style');

	        var buttons = overlay.querySelectorAll('button, .close-button');
	        [].forEach.call(buttons, function (el) {
	            el.addEventListener('click', function() {
	                overlay.classList.add('transparent');
	                setTimeout(function () {
	                    overlay.parentNode.removeChild(overlay);
	                }, 1000);
	            });
	        });

	        overlay.querySelector('.modal-confirm').addEventListener('click', confirmation);

	        overlay.querySelector('.overlay-title').innerHTML = title;
	        overlay.querySelector('.overlay-content').innerHTML = content;

	        overlay.addEventListener('click', function () {
	            overlay.querySelector('.page').classList.add('pulse');
	            overlay.querySelector('.page').addEventListener('webkitAnimationEnd', function(e) {
	                e.target.classList.remove('pulse');
	            });
	        });

	        overlay.querySelector('.page').addEventListener('click', function(e) {
	            e.stopPropagation();
	        });

	        document.body.appendChild(overlay);
	    }

	    function update_output_credentials() {
	        var ul = document.createElement('ul');

	        var display_credentials = credentials.concat(file_credentials);

	        for (var key in display_credentials) {
	            if (display_credentials.hasOwnProperty(key)) {
	                var c = Credentials.sanitize_credential(display_credentials[key]);
	                ul.innerHTML += '<li>' + c.url + ' : ' + c.username + ' - ' + c.password + ' - ' + c.priority + '</li>';
	            }
	        }

	        import_output.innerHTML = '';
	        import_output.appendChild(ul);
	    }

	    function parse_json(text) {
	        var result = [];

	        try {
	            var json = JSON.parse(text);

	            for (var key in json) {
	                if (json.hasOwnProperty(key)) {
	                    var c = json[key];
	                    result.push(c);
	                }
	            }
	        } catch (err) {
	            Analytics.exception('malformed JSON');
	        }

	        return result;
	    }

	    function readerOnLoadEnd(event) {
	        if (event.target.readyState == FileReader.DONE) {
	            file_credentials = file_credentials.concat(parse_json(event.target.result));
	            update_output_credentials();
	        } else {
	            Analytics.exception('File importation error');
	        }
	    }

	    function import_file(e) {
	        Analytics.interaction('Importer', 'file added');

	        var files = e.target.files;
	        var len = files.length;

	        for (var i = 0; i < len; ++i) {
	            var reader = new FileReader();
	            reader.onloadend = readerOnLoadEnd;
	            reader.readAsText(files[i]);
	        }
	    }

	    function import_json() {
	        Analytics.interaction('Importer', 'JSON added');

	        credentials = parse_json(import_json_field.value);
	        update_output_credentials();
	    }

	    function import_credentials(e) {
	        Analytics.interaction('Importer', 'imported');

	        var new_credentials = credentials.concat(file_credentials);

	        for (var key in new_credentials) {
	            if (new_credentials.hasOwnProperty(key)) {
	                CredentialStorage.addCredential(new_credentials[key]);
	            }
	        }

	        credentials = [];
	        file_credentials = [];
	        import_output.innerHTML = '';
	        import_file_field.value = '';
	        import_json_field.value = '';

	        e.preventDefault();
	    }

	    function restore_test_input() {
	        var lis = document.querySelectorAll('#test-urls li');
	        if(lis.length == 0) {
	            document.getElementById('test-urls').innerHTML = '<li>http://www.example.com</li>';
	        }
	        test_regex();
	    }

	    function test_regex() {
	        var regex = document.getElementById('test-regex').value;

	        var lis = document.querySelectorAll('#test-urls li');
	        [].forEach.call(lis, function (el) {
	            var re = new RegExp(regex);
	            var url = el.innerText.trim();

	            el.classList.toggle('matched', re.test(url) && url.length > 0);
	        });
	    }

	    function export_credentials(e) {
	        Analytics.interaction('Exporter', 'exported');

	        var data = 'text/json;charset=utf-8,' + encodeURIComponent(CredentialStorage.asJSON());
	        e.target.setAttribute('href', 'data:' + data);
	    }

	    function clear_credentials(e) {
	        Analytics.interaction('Credentials', 'cleared');

	        modal(Translator.translate('clear_credentials_modal_title'), Translator.translate('clear_credentials_modal_text'), CredentialStorage.clearAll);
	        e.preventDefault();
	    }

	    function update_analytics_status(e) {
	        var new_status = analytics_field.checked;

	        if(new_status == false) {
	            // send disabled interaction before disabling
	            Analytics.interaction('Analytics', 'disabled');
	        }
	        Analytics.status(null, new_status);

	        if(new_status == true) {
	            setTimeout(function() {
	                // send enabled interaction after enabling
	                Analytics.interaction('Analytics', 'enabled');
	            }, 2000);
	        }

	        e.preventDefault();
	    }

	    function display_analytics_status(status) {
	        analytics_field.checked = status;
	    }

	    function init() {
	        import_output = document.querySelector('output.import-list');
	        import_file_field = document.getElementById('import-file');
	        import_json_field = document.getElementById('import-json');

	        analytics_field = document.getElementById('analytics-enabled');

	        import_file_field.addEventListener('change', import_file);
	        import_json_field.addEventListener('change', import_json);
	        document.querySelector('button.import-submit').addEventListener('click', import_credentials);

	        document.getElementById('test-urls').addEventListener('blur', restore_test_input);
	        document.getElementById('test-urls').addEventListener('keyup', test_regex);
	        document.getElementById('test-regex').addEventListener('keyup', test_regex);

	        document.querySelector('a.export-credentials').addEventListener('click', export_credentials);

	        document.querySelector('button.clear-all').addEventListener('click', clear_credentials);

	        analytics_field.addEventListener('change', update_analytics_status);
	        Analytics.status(display_analytics_status);

	        document.getElementsByClassName('multipass-version')[0].innerText = chrome.runtime.getManifest()['version'];
	    }

	    return {
	        'init': init
	    };
	}();

	document.addEventListener('DOMContentLoaded', function () {
	    Analytics.view('Option Panel');
	    OptionPanel.init();
	    Credentials.init();
	});


/***/ },
/* 1 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';

	var Storage = __webpack_require__(2);

	module.exports = function() {
	    var manifest = chrome.runtime.getManifest();
	    var browser = manifest.hasOwnProperty('developer') ? 'opera' : manifest.hasOwnProperty('applications') ? 'firefox' : 'chrome';

	    var queue = [];
	    var ga_enabled = false;

	    var init = function(enable) {
	        if(browser == 'firefox' || enable == false) {
	            send = function() {};
	            queue = [];
	            return;
	        }

	        if(ga_enabled === false) {
	            /*eslint-disable */
	            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	            })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

	            ga('create', 'UA-1168006-9', 'auto');
	            ga('set', 'appName', manifest['short_name']);
	            ga('set', 'appVersion', manifest['version']);
	            ga('set', 'appInstallerId', browser);
	            ga('set', 'checkProtocolTask', function(){}); // Removes failing protocol check. @see: http://stackoverflow.com/a/22152353/1958200
	            // disable displayfeatures as it generates a lot of hits and we are past the limit
	            // ga('require', 'displayfeatures');
	            ga_enabled = true;
	            /*eslint-enable */
	        }

	        send = function(type, a, b, c, d) {
	            /*eslint-disable */
	            ga('send', type, a, b, c, d);
	            /*eslint-enable */
	        };

	        for(var i in queue) {
	            send.apply(send, queue[i]);
	        }
	        queue = [];
	    };

	    var send = function(type, a, b, c, d) {
	        queue.push([type, a, b, c, d]);
	    };

	    var screen = function(name) {
	        send('screenview', { 'screenName': name });
	    };

	    var event = function(event, action, value) {
	        send('event', event, action, value, { nonInteraction: true });
	    };

	    var interaction = function(event, action, value) {
	        send('event', event, action, value);
	    };

	    var exception = function(description) {
	        send('exception', { 'exDescription': description, 'exFatal': false });
	    };

	    var status = function(callback, new_value) {
	        if(typeof(callback) === 'function') {
	            Storage.register('analytics_enabled', callback);
	        }

	        if(typeof(new_value) !== 'undefined') {
	            Storage.set('analytics_enabled', new_value);
	        } else if(typeof(callback) === 'function') {
	            Storage.get('analytics_enabled', callback, true);
	        }
	    };

	    status(init);

	    return {
	        'view': screen,
	        'event': event,
	        'interaction': interaction,
	        'exception': exception,
	        'status': status
	    };
	}();


/***/ },
/* 2 */
/***/ function(module, exports) {

	'use strict';

	module.exports = function() {
	    function detectStorageNamespace(storage) {
	        var ns = 'local';

	        if(storage.sync) {
	            ns = 'sync';

	            try {
	                var test = window[ns];
	                var x = '__storage_test__';

	                test.setItem(x, x);
	                test.removeItem(x);
	            }
	            catch(e) {
	                ns = 'local';
	            }
	        }

	        return ns;
	    }

	    var storage = chrome.storage;
	    var storageNamespace = detectStorageNamespace(storage);
	    var dataStore = storage[storageNamespace];

	    var listener_callbacks = {};

	    function get(key, callback, default_value) {
	        dataStore.get(key, function(result) {
	            if (result.hasOwnProperty(key)) {
	                if(typeof(listener_callbacks[key]) != 'undefined') {
	                    for (var i in listener_callbacks[key]) {
	                        if (listener_callbacks[key].hasOwnProperty(i)) {
	                            listener_callbacks[key][i](result[key]);
	                        }
	                    }
	                }

	                if(typeof(callback) !== 'undefined') {
	                    callback(result[key]);
	                }
	            } else if(typeof(default_value) !== 'undefined') {
	                callback(default_value);
	            }
	        });
	    }

	    function set(key, value) {
	        var data = {};
	        data[key] = value;
	        dataStore.set(data);
	    }

	    function register(key, callback) {
	        if(typeof(listener_callbacks[key]) == 'undefined') {
	            listener_callbacks[key] = [];
	        }
	        listener_callbacks[key].push(callback);

	        storage.onChanged.addListener(function (changes, namespace) {
	            if (namespace === storageNamespace && changes.hasOwnProperty(key)) {
	                if(typeof(callback) !== 'undefined') {
	                    callback(changes[key].newValue);
	                }

	                return changes[key].newValue;
	            }
	        });
	    }

	    return {
	        'get': get,
	        'set': set,
	        'register': register
	    };
	}();


/***/ },
/* 3 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';

	var Analytics = __webpack_require__(1);
	var CredentialStorage = __webpack_require__(4);
	var Storage = __webpack_require__(2);
	var Translator = __webpack_require__(5);

	module.exports = function() {
	    var password_stars_class = 'password-stars';
	    var password_real_class = 'password-real';

	    var storage_key = 'temporary-credentials';

	    function sanitize_credential(credential) {
	        var fields = ['url', 'username', 'password', 'priority'];
	        var result = {};

	        for(var f in fields) {
	            if (fields.hasOwnProperty(f) && credential.hasOwnProperty(fields[f])) {
	                var value = credential[fields[f]];
	                if(typeof(value) === 'string') {
	                    console.log('plop');
	                    value = value.replace(/[\u00A0-\u9999<>\&\'\"]/gim, function (i) {
	                        return '&#' + i.charCodeAt(0) + ';';
	                    });
	                }
	                result[fields[f]] = value;
	            }
	        }

	        return result;
	    }

	    function display_credentials(credentials) {
	        var container = document.getElementsByClassName('credentials')[0];
	        container.innerHTML = '';

	        credentials = Object.keys(credentials).map(function(e) {
	            return credentials[e];
	        });
	        credentials.sort(CredentialStorage.sortCredentials);

	        for (var key in credentials) {
	            if (credentials.hasOwnProperty(key)) {
	                // We sanitize upon display only because the username and
	                // password might contain chars that could be transformed
	                // thus making the credential invalid.
	                var c = sanitize_credential(credentials[key]);

	                container.innerHTML +=
	                    '<tr>' +
	                        '<td class="url" title="' + c.url + '">' + c.url + '</td>' +
	                        '<td class="username" title="' + c.username + '">' + c.username + '</td>' +
	                        '<td class="password">' +
	                            '<span class="' + password_stars_class + '">***</span>' +
	                            '<span class="' + password_real_class + '">' + c.password + '</span>' +
	                            '<button class="show-password">' + Translator.translate('show_hide_password') + '</button>' +
	                        '</td>' +
	                        '<td class="priority">' + (c.priority || 1) + '</td>' +
	                        '<td class="action">' +
	                            '<button class="remove" data-url="' + c.url + '">' + Translator.translate('remove_credential') + '</button>' + '' +
	                            '<button class="edit" data-url="' + c.url + '">' + Translator.translate('edit_credential') + '</button>' + '' +
	                        '</td>' +
	                    '</tr>';
	            }
	        }
	    }

	    function togglePassword(e) {
	        var password = e.target.parentNode;
	        var star = password.getElementsByClassName(password_stars_class)[0];
	        var real = password.getElementsByClassName(password_real_class)[0];

	        var password_shown = star.style.display == 'none';
	        star.style.display = password_shown ? 'inline' : 'none';
	        real.style.display = password_shown ? 'none' : 'inline';

	        Analytics.interaction('Credentials', 'password visibility toggled', password_shown ? 'hide' : 'show');
	    }

	    function submit(e) {
	        e.preventDefault();

	        var url = document.getElementById('url');
	        var username = document.getElementById('username');
	        var password = document.getElementById('password');
	        var priority = document.getElementById('priority');

	        var values = {
	            url: url.value,
	            username: username.value,
	            password: password.value,
	            priority: priority.value
	        };

	        var valid = true;
	        for (var key in values) {
	            if (values.hasOwnProperty(key)) {
	                var v = values[key];

	                if(v === '') {
	                    Analytics.exception('Form error : ' + key + ' is empty.');
	                    valid = false;
	                }
	            }
	        }

	        if(valid) {
	            var old = document.querySelector('tr.editing .url');
	            if(old && old.innerText.length > 0) {
	                CredentialStorage.removeCredential(old.innerText);
	            }

	            CredentialStorage.addCredential(values);

	            url.value = '';
	            username.value = '';
	            password.value = '';
	            priority.value = 1;

	            reset_form();

	            Analytics.interaction('Credentials', 'added');
	        }
	    }

	    function remove(e) {
	        var url = e.target.getAttribute('data-url');
	        CredentialStorage.removeCredential(url);

	        Analytics.interaction('Credentials', 'removed');
	    }

	    function edit(e) {
	        reset_form();

	        var url = document.getElementById('url');
	        var username = document.getElementById('username');
	        var password = document.getElementById('password');
	        var priority = document.getElementById('priority');

	        var tr = e.target.closest('tr');

	        tr.classList.add('editing');

	        url.value = tr.getElementsByClassName('url')[0].textContent;
	        username.value = tr.getElementsByClassName('username')[0].textContent;
	        password.value = tr.getElementsByClassName('password-real')[0].textContent;
	        priority.value = tr.getElementsByClassName('priority')[0].textContent;

	        document.getElementsByClassName('credential-form-submit')[0].textContent = Translator.translate('edit_credential');
	    }

	    function reset_form() {
	        var el = document.querySelector('tr.editing');
	        if(el) el.classList.remove('editing');

	        document.getElementsByClassName('credential-form-submit')[0].textContent = Translator.translate('add_credential');
	    }

	    function init() {
	        CredentialStorage.register(display_credentials);

	        document.getElementById('credential-form').addEventListener('submit', submit);

	        document.addEventListener('click', function(e) {
	            if(e.target.matches('.credential-form-reset')) {
	                e.stopPropagation();
	                reset_form(e);
	            }
	            if(e.target.matches('.remove')) {
	                e.stopPropagation();
	                remove(e);
	            }
	            if(e.target.matches('.edit')) {
	                e.stopPropagation();
	                edit(e);
	            }
	            if(e.target.matches('.show-password')) {
	                e.stopPropagation();
	                togglePassword(e);
	            }
	        });

	        Storage.get(storage_key, function(result) {
	            document.getElementById('url').value = result.url || '';
	            document.getElementById('username').value = result.username || '';
	            document.getElementById('password').value = result.password || '';
	            document.getElementById('priority').value = result.priority || 1;

	            Storage.set(storage_key, {});
	        });

	        addEventListener('unload', function () {
	            var url = document.getElementById('url').value;
	            var username = document.getElementById('username').value;
	            var password = document.getElementById('password').value;
	            var priority = document.getElementById('priority').value;

	            var values = {
	                url: url,
	                username: username,
	                password: password,
	                priority: priority
	            };
	            chrome.extension.getBackgroundPage().Storage.set.apply(this, [storage_key, values]);
	        });
	    }

	    return {
	        'init': init,
	        'sanitize_credential': sanitize_credential
	    };
	}();


/***/ },
/* 4 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';

	var Storage = __webpack_require__(2);

	module.exports = function() {
	    var credentials = {};

	    var variable_name = 'credentials';

	    function _key(key) {
	        var hash = 0;
	        var len = key.length;

	        if (len === 0) {
	            return hash;
	        }

	        for (var i = 0; i < len; i++) {
	            var chr = key.charCodeAt(i);
	            hash = ((hash << 5) - hash) + chr;
	            hash |= 0; // Convert to 32bit integer
	        }
	        return hash;
	    }

	    function addCredential(credential) {
	        credentials[_key(credential.url)] = credential;
	        Storage.set(variable_name, credentials);

	        return credential;
	    }

	    function removeCredential(url) {
	        var key = _key(url);
	        var credential = credentials[key];
	        delete credentials[key];
	        Storage.set(variable_name, credentials);

	        return credential;
	    }

	    function clearAll() {
	        credentials = {};
	        Storage.set(variable_name, credentials);
	    }

	    function getCredentials(status) {
	        var found = [];
	        for (var key in credentials) {
	            if (credentials.hasOwnProperty(key)) {
	                var re = new RegExp(credentials[key].url);
	                if (re.test(status.url)) {
	                    found.push(credentials[key]);
	                }
	            }
	        }

	        found.sort(sortCredentials);

	        return found;
	    }

	    function register(callback) {
	        Storage.register(variable_name, callback);
	        callback(credentials);
	    }

	    function updateCredentials(result)
	    {
	        // convert from the old storage format
	        if(Array.isArray(result)) {
	            credentials = {};

	            for (var key in result) {
	                if (result.hasOwnProperty(key)) {
	                    credentials[_key(result[key].url)] = result[key];
	                }
	            }

	            Storage.set(variable_name, credentials);
	        } else {
	            credentials = result;
	        }
	    }

	    function sortCredentials(a, b)
	    {
	        if(typeof(a.priority) === 'undefined') a.priority = 1;
	        if(typeof(b.priority) === 'undefined') b.priority = 1;

	        return a.priority - b.priority;
	    }

	    // retrieve the credentials from storage
	    Storage.get(variable_name, updateCredentials);
	    register(updateCredentials);

	    return {
	        'register': register,

	        'getCredentials': getCredentials,

	        'sortCredentials': sortCredentials,

	        'removeCredential': removeCredential,
	        'clearAll': clearAll,
	        'addCredential': addCredential,
	        'asJSON': function() { return JSON.stringify(credentials); },
	    };
	}();


/***/ },
/* 5 */
/***/ function(module, exports) {

	'use strict';

	module.exports = function() {
	    function translate(key) {
	        return chrome.i18n.getMessage(key);
	    }

	    function translateHtml() {
	        var els = document.querySelectorAll('[data-i18n]');

	        [].forEach.call(els, function (el) {
	            el.innerText = translate(el.getAttribute('data-i18n'));
	        });
	    }

	    translateHtml();

	    return {
	        'translate': translate
	    };
	}();


/***/ }
/******/ ]);