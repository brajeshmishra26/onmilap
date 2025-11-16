import {
    initializeApp
} from 'https://www.gstatic.com/firebasejs/10.5.2/firebase-app.js'

import {
    getAnalytics
} from 'https://www.gstatic.com/firebasejs/10.5.2/firebase-analytics.js'

import {
    getAuth
} from 'https://www.gstatic.com/firebasejs/10.5.2/firebase-auth.js'
import {
    getFirestore
} from 'https://www.gstatic.com/firebasejs/10.5.2/firebase-firestore.js'
import {
    getMessaging, getToken
} from 'https://www.gstatic.com/firebasejs/10.5.2/firebase-messaging.js'

let hasRequestedFBPermission = false;

const firebaseConfig = {
    apiKey: $('.web_push_service_variables > .apiKey').text(),
    authDomain: $('.web_push_service_variables > .authDomain').text(),
    projectId: $('.web_push_service_variables > .projectId').text(),
    messagingSenderId: $('.web_push_service_variables > .messagingSenderId').text(),
    appId: $('.web_push_service_variables > .appId').text(),
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

const firebase_requestPermission = async () => {
    if ('Notification' in window) {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            console.log('Notification permission granted.');
            return true;
        } else {
            console.log('Notification permission denied.');
            return false;
        }
    } else {
        console.error('This browser does not support notifications.');
        return false;
    }
};

const firebase_getDeviceToken = async () => {
    try {
        const token = await getToken(messaging, {
            serviceWorkerRegistration: firebase_sw_reg
        });
        add_push_subscriber(token, 'firebase');
    } catch (error) {
        console.error('Error getting device token:', error);
    }
};

document.querySelectorAll('.site_records').forEach(element => {
    element.addEventListener('click', async () => {
        if (!hasRequestedFBPermission) {
            if (firebase_sw_reg) {
                const permissionGranted = await firebase_requestPermission();
                if (permissionGranted) {
                    await firebase_getDeviceToken();
                }
                hasRequestedFBPermission = true;
            } else {
                console.log('Service worker is not registered yet.');
            }
        }
    });
});