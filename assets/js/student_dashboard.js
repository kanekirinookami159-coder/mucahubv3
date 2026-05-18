/* =========================
PROFILE PANEL
========================= */

const profileBtn = document.getElementById("profileBtn")
const profilePanel = document.getElementById("profilePanel")

if(profileBtn){

profileBtn.onclick = () => {

profilePanel.classList.add("active")

}

}

function closeProfile(){

profilePanel.classList.remove("active")

}


/* =========================
LOAD PROFILE
========================= */

function loadProfile(){

document.getElementById("studentName").value =
localStorage.getItem("studentName") || ""

document.getElementById("studentEmail").value =
localStorage.getItem("studentEmail") || ""

document.getElementById("studentGrade").value =
localStorage.getItem("studentGrade") || ""

document.getElementById("studentSection").value =
localStorage.getItem("studentSection") || ""

}


/* =========================
SAVE PROFILE
========================= */

function saveProfile(){

localStorage.setItem(
"studentName",
document.getElementById("studentName").value
)

localStorage.setItem(
"studentEmail",
document.getElementById("studentEmail").value
)

localStorage.setItem(
"studentGrade",
document.getElementById("studentGrade").value
)

localStorage.setItem(
"studentSection",
document.getElementById("studentSection").value
)

alert("Profile Saved!")

}

loadProfile()