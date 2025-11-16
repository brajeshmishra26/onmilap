importScripts("https://www.gstatic.com/firebasejs/10.5.2/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.5.2/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "",
    authDomain: "",
    projectId: "",
    messagingSenderId: "",
    appId: "",
});

const messaging = firebase.messaging();
messaging.onBackgroundMessage((payload) => {
    const notificationTitle = payload.data.title;
    const notificationOptions = {
        body: payload.data.body,
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener("install", (event) => {
    event.waitUntil(self.skipWaiting());
});
