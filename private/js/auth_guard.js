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
	/* firebase config */
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