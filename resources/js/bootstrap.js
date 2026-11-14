/**
 * Bienestar UPEMOR — Configuración de Cliente HTTP (Axios).
 *
 * Configura la biblioteca Axios asignando encabezados globales para peticiones AJAX
 * protegidas por CSRF y detección de solicitudes XMLHttpRequest en Laravel.
 *
 * @module resources/js/bootstrap
 */

import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
