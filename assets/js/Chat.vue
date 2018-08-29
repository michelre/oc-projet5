<template>
    <div>
        <ul class="p-0">
            <li
                class="list-unstyled mb-2"
                v-for="message in messages"
            >
                <div class="d-flex">
                    <span class="font-weight-bold mr-3">{{getSenderInfo(message.sender)}}</span>
                    <div>{{message.body}}</div>
                </div>

            </li>
        </ul>
        <p class="font-weight-bold" v-if="messages.length === 0">Pas de messages</p>
        <textarea class="form-control" v-model="messageToSend"></textarea>
        <button @click="sendMessage" class="btn btn-primary">Envoyer</button>
    </div>
</template>

<script>
  import axios from 'axios'

  export default {
    name: 'Chat',
    props: {
      fromUser: String,
      toUser: String,
      modalDisplayed: Boolean,
      selector: String,
    },
    data() {
      return {
        messages: [],
        messageToSend: '',
        currentInterval: null,
        autocomplete: ''
      }
    },
    beforeDestroy() {
      clearInterval(this.currentInterval)
    },
    methods: {
      getMessages() {
        this.clearInterval();
        if (this.fromUser && this.toUser) {
          this.currentInterval = setInterval(() => {
            axios.get(`/projet5/symfony/web/app_dev.php/messages/${this.fromUser}/${this.toUser}`).then(d => {
              this.messages = d.data;
            })
          }, 2000)
        }
      },
      sendMessage(e) {
        e.preventDefault()
        axios.post(`/projet5/symfony/web/app_dev.php/message/send`, {body: this.messageToSend, from: this.fromUser, to: this.toUser}).then(d => {
          this.messages = d.data
          this.messageToSend = ''
        })
      },
      getSenderInfo(sender) {
        const _sender = JSON.parse(sender)
        return _sender.pseudo
      },
      clearInterval() {
        clearInterval(this.currentInterval)
      }
    }
  }
</script>

<style></style>
