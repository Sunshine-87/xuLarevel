import Vue from 'vue/dist/vue.js'
import App from './App.vue'
import VueRouter from 'vue-router'
import VueResource from 'vue-resource'
Vue.use(VueResource);
Vue.use(VueRouter)

import Example from './components/Example.vue'

const router = new VueRouter({
  mode: 'history',
  base: __dirname,
  routes: [
    { path: '/example', component: Example }
  ]
})

new Vue(Vue.util.extend({ router }, App)).$mount('#app')