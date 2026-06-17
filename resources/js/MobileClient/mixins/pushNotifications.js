export default {
    data() {
        return {
            pushChannel: null,
        };
    },

    mounted() {
        console.log("we are here")
        this.setupPushListener();
    },

    beforeUnmount() {
        this.pushChannel?.close();
    },

    methods: {
        setupPushListener() {
            console.log("test 1")
            if (!('BroadcastChannel' in window))
            {
                console.log("test 2")
                return;
            }

            console.log("test 3")
            this.pushChannel = new BroadcastChannel('push-notifications');
            this.pushChannel.onmessage = (event) => {
                console.log("test 4")
                if (event.data?.type === 'PUSH_RECEIVED' && this.$notify) {
                    console.log("test message", event)
                    this.$notify({
                        title: event.data.title,
                        text: event.data.body,
                        type: 'success',
                    });
                }
            };

            // Слушаем клик по нативному уведомлению
            navigator.serviceWorker?.addEventListener('message', (event) => {
                console.log("Testtstst")
                if (event.data?.type === 'NOTIFICATION_CLICKED' && event.data.url) {
                    window.location.href = event.data.url;
                }
            });
        },
    },
};
