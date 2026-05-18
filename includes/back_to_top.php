<!-- BACK TO TOP BUTTON -->

<button id="backToTopBtn" title="Go to top">
<i class="fas fa-arrow-up"></i>
</button>

<style>

#backToTopBtn{
position: fixed;
bottom: 20px;
right: 20px;
width: 40px;
height: 40px;
background: #556B2F; /* olive green */
color: white;
border: none;
border-radius: 6px;
cursor: pointer;
font-size: 16px;
display: none;
z-index: 999;
transition: 0.3s;
}

#backToTopBtn:hover{
background:#6B8E23;
}

</style>

<script>

const backToTopBtn = document.getElementById("backToTopBtn");

window.onscroll = function(){

if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200){
backToTopBtn.style.display = "block";
} 
else{
backToTopBtn.style.display = "none";
}

};

backToTopBtn.onclick = function(){

window.scrollTo({
top:0,
behavior:"smooth"
});

};

</script>