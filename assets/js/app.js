require('../css/app.css');
import Vue from 'vue';
import Chat from './Chat.vue'

let vms = [];

document.querySelectorAll('.chat-app').forEach((el) => {
  const vm = new Vue({
    el,
    template: '<Chat :from-user="fromUser" :to-user="toUser" :selector="selector"/>',
    components: {Chat},
    beforeMount: function () {
      this.fromUser = this.$el.attributes['data-user-from'].value;
      this.toUser = this.$el.attributes['data-user-to'].value;
      this.selector = el.id
    }
  });
  vms = vms.concat(vm)
})


document.querySelectorAll('.btn-chat').forEach(e => {
  e.addEventListener('click', (event) => {
    const vm = vms.find((vm) => {
      return `${event.target.dataset.target}--vue` === vm.selector
    });
    vm.$children[0].getMessages()
  })
});

document.querySelectorAll('.modal .close').forEach(e => {
  e.addEventListener('click', (event) => {
    console.log(event.target.dataset)
    const vm = vms.find((vm) => {
      return `${event.target.dataset.target}--vue` === vm.selector
    })
    vm.$children[0].clearInterval()
  })
})

