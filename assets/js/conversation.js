var chatMessages = [
    {
        name: "ms1",
        msg: "👋Hi​! I'm a Milap assistant. Let me know if you have any questions regarding our tool or set up an account  to learn more!",
        delay: 300,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms2",
        msg: "Milap, I have a Question",
        delay: 3000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms3",
        msg: "Sure! Ask me anything!",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms4",
        msg: "What is onMilap?",
        delay: 2000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms5",
        msg: "onMilap is a video chat platform designed for virtual meetings, events, social networking, and online collaboration.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms6",
        msg: "What are the key features of onMilap?",
        delay: 2000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms7",
        msg: "HD video calls, screen sharing, live streaming, breakout rooms, real-time chat, and interactive Q&A.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms8",
        msg: "Is onMilap available on mobile devices?",
        delay: 2000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms9",
        msg: "Yes, onMilap supports both mobile and desktop platforms for seamless connectivity.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms10",
        msg: "Does onMilap support group video calls?️",
        delay: 4000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms11",
        msg: "Yes, it allows multiple participants to join a video call simultaneously.",
        delay: 1000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms12",
        msg: "Can I share files during a video call?",
        delay: 3000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms13",
        msg: "Yes, documents, images, and other files can be shared via chat.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms14",
        msg: "Can onMilap be used for virtual dating?",
        delay: 2000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms15",
        msg: "Yes, it offers private video chats for matchmaking and dating events.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms16",
        msg: "Is onMilap safe for online dating?",
        delay: 2000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms17",
        msg: "Yes, it includes security features like identity verification and reporting tools.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms18",
        msg: "Can I send messages before starting a video call?",
        delay: 2000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms19",
        msg: "Yes, users can chat before initiating a video call.",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
    {
        name: "ms20",
        msg: "Great, Thank You!❤️",
        delay: 4000,
        align: "left",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/7.jpg"
    },
    {
        name: "ms21",
        msg: "It is nice assisting you! 😊 If you’d like to explore onMilap further, our FAQ section provides in-depth answers to common questions, while our demo video offers a step-by-step walkthrough of the platform’s features. Whether you're looking to host virtual events, connect with others, or enhance team collaboration, we’ve got you covered! Feel free to check out these resources or reach out to our support team for personalized assistance., Thank You!❤️",
        delay: 3000,
        align: "right",
        showTime: true,
        time: "19:58",
        img: "assets/img/author-image/8.jpg"
    },
];
var chatDelay = 0;

function onRowAdded() {
    $('.chat-container').animate({
        scrollTop: $('.chat-container').prop('scrollHeight')
    });
};
$.each(chatMessages, function(index, obj) {
    chatDelay = chatDelay + 1000;
    chatDelay2 = chatDelay + obj.delay;
    chatDelay3 = chatDelay2 + 10;
    scrollDelay = chatDelay;
    chatTimeString = " ";
    msgname = "." + obj.name;
    msginner = ".messageinner-" + obj.name;
    spinner = ".sp-" + obj.name;
    if (obj.showTime == true) {
        chatTimeString = "<span class='message-time'>" + obj.time + "</span>";
    }
    $(".chat-message-list").append("<li class='message-" + obj.align + " " + obj.name + "' hhidden><div class='sp-" + obj.name + "'><span class='spinme-" + obj.align + "'><div class='spinner'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div></span></div><div class='messageinner-" + obj.name + "' hhidden><img src='" + obj.img +"'><span class='message-text'>" + obj.msg + chatTimeString + "</span></div></li>");

    $(msgname).delay(chatDelay).fadeIn();
    $(spinner).delay(chatDelay2).hide(1);
    $(msginner).delay(chatDelay3).fadeIn();
    setTimeout(onRowAdded, chatDelay);
    setTimeout(onRowAdded, chatDelay3);
    chatDelay = chatDelay3;
});