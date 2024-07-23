import Vue from 'vue';
import App from './App.vue';
import { BootstrapVue, IconsPlugin, ToastPlugin } from 'bootstrap-vue';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';
import VueTheMask from 'vue-the-mask';

Vue.config.productionTip = false;

Vue.use(BootstrapVue);
Vue.use(IconsPlugin);
Vue.use(ToastPlugin);
Vue.use(VueTheMask);

new Vue({
    render: h => h(App),
}).$mount('#app');