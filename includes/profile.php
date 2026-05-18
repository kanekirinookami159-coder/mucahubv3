<!-- FLOATING PROFILE BUTTON -->

<button id="profileBtn" style="

position:fixed;
bottom:20px;
left:20px;
background:white;
color:black;
border:none;
padding:12px 16px;
border-radius:50px;
box-shadow:0 2px 8px rgba(0,0,0,0.3);
cursor:pointer;
z-index:1001;

">

<i class="fa fa-user"></i>

</button>


<!-- PROFILE PANEL -->

<div id="profilePanel" class="sidepanel">

<div class="profile-panel-header">
    <button class="closeBtn" onclick="closeProfile()">✖</button>
    <button class="backBtn" onclick="closeProfile()">Back</button>
</div>

<h3>Student Profile</h3>

<label>Name</label>
<input type="text" id="studentName" value="Juan Dela Cruz">

<label>Email</label>
<input type="email" id="studentEmail" value="student@email.com">

<label>Grade Level</label>
<input type="text" id="studentGrade" value="Grade 10">

<label>Section</label>
<input type="text" id="studentSection" value="Section A">

<button onclick="saveProfile()">Save</button>

</div>