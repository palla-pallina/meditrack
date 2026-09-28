import { initializeApp } from "https://www.gstatic.com/firebasejs/10.13.2/firebase-app.js";

import {
    getAuth,
    onAuthStateChanged
} from "https://www.gstatic.com/firebasejs/10.13.2/firebase-auth.js";

import {
    getDatabase,
    ref,
    get
} from "https://www.gstatic.com/firebasejs/10.13.2/firebase-database.js";

const firebaseConfig = {
    apiKey: "AIzaSyBRm3dUt3HreTquM_Ba9JfYmZD2CLVV0Gc",
    authDomain: "meditrack-59cca.firebaseapp.com",
    databaseURL: "https://meditrack-59cca-default-rtdb.asia-southeast1.firebasedatabase.app/",
    projectId: "meditrack-59cca",
    storageBucket: "meditrack-59cca.firebasestorage.app",
    messagingSenderId: "656003988692",
    appId: "1:656003988692:web:badfe96b7d830837a4cf41"
};

const app = initializeApp(firebaseConfig);

const auth = getAuth(app);

const database = getDatabase(app);

export function requireRole(role){

    onAuthStateChanged(auth, async(user)=>{

        if(!user){

            window.location.href="../../index.html";
            return;
        }

        const snapshot = await get(
            ref(database,"users/"+user.uid)
        );

        if(!snapshot.exists()){

            auth.signOut();

            window.location.href="../../index.html";

            return;

        }

        const profile = snapshot.val();

        if(profile.userType !== role){

            alert("Access denied.");

            window.location.href="../../index.html";

        }

    });

}