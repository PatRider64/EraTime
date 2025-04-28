import './styles/app.scss';

// JQUERY
import * as $ from 'jquery';
// create global $ and jQuery variables
global.$ = global.jQuery = $;

import './js/jquery.dataTables.min.js';
import './js/jquery.dataTables.fnReloadAjax.js';
import './js/jquery.dataTables.sorting.min.js';
import './js/jquery.dataTables.yadcf.min.js';

// Bootstrap & Bootstrap-js
import './bootstrap';
import { Tooltip, Toast, Popover } from 'bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// import 'popper.js';
import '@popperjs/core'

//End Import JS Libraries
console.log('Webpack started. File : assets/app.js');