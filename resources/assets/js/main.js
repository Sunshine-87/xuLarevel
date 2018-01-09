import Vue from 'vue/dist/vue.js'
import App from './App.vue'
import VueRouter from 'vue-router'
import VueResource from 'vue-resource'
import ElementUI from 'element-ui'  
import 'element-ui/lib/theme-chalk/index.css'

Vue.use(VueResource);
Vue.use(VueRouter);
Vue.use(ElementUI);

import Example from './components/Example.vue'

const router = new VueRouter({
  mode: 'history',
  base: __dirname,
  routes: [
    { path: '/example', component: Example }
  ]
})

new Vue(Vue.util.extend({ router }, App)).$mount('#app')