import { io } from "socket.io-client";


    const instance = io(import.meta.env.VITE_SOCKET_URL, {
        auth: {
            token: import.meta.env.VITE_SOCKET_TOKEN
        },
        withCredentials: true,
        transports: ["websocket"],
        reconnectionAttempts: 5 
    })
    instance.on("connect", () => {
        console.log('Connected to socket Successfully')
    });
    export{ instance }

