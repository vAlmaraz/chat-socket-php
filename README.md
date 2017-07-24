# Chat Server
## Simple socket server (and javascript client) to create a chat app

This is a proof of concept about a simple socket to communicate several clients with each others and send messages.

## Components

- Ratchet: PHP framework to create web socket.
- Redis: Database management to storage client session information.

## Install and execute

You only need to execute [app/index.php](app/index.php). It could be in background mode.

For example
```
# nohup php index.php > chat.log 2>&1 &
```

## Javascript client

Configure a web server (Nginx or Apache) and make the public folder as root directory. It contains a web client to connect and test the socket server.

## Android client

I have written also a simple [Android client](https://github.com/vAlmaraz/chat-socket-android) that uses this socket to connect with other users.
