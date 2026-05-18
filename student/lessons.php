<?php
include "../config/database.php";
include "../config/config.php";
include "../includes/functions.php";

checkLogin();

$lessons = $conn->query("SELECT * FROM assignments WHERE file IS NOT NULL");
?>

<link rel="stylesheet" href="../assets/css/style.css">

<!-- SIDEBAR -->
<?php include "../includes/sidebar_student.php"; ?>

<div class="main">

<h2>Lessons</h2>

<div class="card">

<table border="1" width="100%">

<tr>
<th>Lesson</th>
<th>Download</th>
</tr>

<?php while($row=$lessons->fetch_assoc()){ ?>

<tr>

<td><?php echo $row['title']; ?></td>

<td>
<a href="../assets/uploads/lessons/<?php echo $row['file']; ?>" onclick="saveRecentMaterial('Lesson', '<?php echo htmlspecialchars(addslashes($row['title']), ENT_QUOTES, 'UTF-8'); ?>', this.href)">
Download
</a>
</td>

</tr>

<?php } ?>

</table>

</div>

</div>

<!-- FLOAT COMPONENTS -->
<?php include "../includes/float_student.php"; ?>

<script src="../assets/js/notifications.js"></script>
<script>
function saveRecentMaterial(type, title, url) {
    try {
        const materials = JSON.parse(localStorage.getItem('recentMaterials')) || [];
        const now = new Date().toLocaleString();
        const newItem = { type: type || 'Material', title: title || 'Untitled', date: now, url };
        const updated = [newItem].concat(materials.filter(item => item.url !== url || item.title !== title));
        localStorage.setItem('recentMaterials', JSON.stringify(updated.slice(0, 10)));
    } catch (error) {
        console.error('Unable to save recent material:', error);
    }
}
</script>