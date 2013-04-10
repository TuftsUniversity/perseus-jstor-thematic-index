<?php
header('Content-Type: text/html; charset=utf-8');

$doi = $_GET['d'];

header('Content-Type: text/html; charset=utf-8');

if (! is_numeric($doi)) {
  $doi = 0;
}
?><html>
<head>
<title>Thematic Index of Classics in JStor</title>
<script type="text/javascript" src="jquery.js"></script>
<link rel="stylesheet" type="text/css" href="jstor.css"/>
</head>
<body>

<div class="header"><a href="index.php">Thematic Index of Classics in JStor</a></div>

<div class="topics">

<?php
try {

  $db = new PDO("sqlite:jstor.t300.db");

  $article_stmt = $db->prepare('select * from articles where doi = :doi');
  $article_stmt->bindParam(':doi', $doi, PDO::PARAM_INT);
  $article_stmt->execute();
  
  $results = $article_stmt->fetchAll();
  $row = $results[0];

  $doi = $row['doi'];
  
  $year = $row['year'];

  $ref = "";
  if ($row['title'] != '') {
	$ref .= "<div><span class='booktitle'>" . $row['title'] . "</span></div>";
  }
  if ($row['author'] != '') {
	$ref .= "<div>".$row['author']."</div>";
  }
  $ref .= "<div>";
  if ($row['journal'] != '') {
	$ref .= "<span>".$row['journal'].".</span> ";
  }
  if ($row['display_date'] != '') {
	$ref .= "<span>(".$row['display_date'].")</span> ";
  }
  if ($row['pages'] != '') {
	$ref .= "<span>pp. ".$row['pages']."</span>";
  }
  $ref .= "</div>";

?>
<div class='side_box'><h3>Article Themes</h3>
<p>
This page lists the automatically detected themes present in this article.
  The words shown for each theme reflect the overall content of the theme, and 
may not appear in this particular article.
</p>
<p>
  For each theme, you can also find other articles from the same year.
</p>
<p>
The work presented here was developed by David Mimno under the
  Cybereditions Project, an effort led by the Perseus Project at Tufts
University and funded by the Mellon Foundation.
</p>
</div>

<div class="articleheader">
<?php print($ref) ?>
   <a class="jstorref" href="http://www.jstor.org/stable/<?php print($doi) ?>">Full text at JStor</a>
</div>

<?php

  $topic_stmt = $db->prepare('select * from article_topic_counts where doi = :doi  order by word_count DESC');
  $topic_stmt->bindParam(':doi', $doi, PDO::PARAM_INT);
  $topic_stmt->execute();

  foreach ($topic_stmt->fetchAll() as $row) {
	$topic_word_list = array();
	$NUM_WORDS = 30;

	$topic_id = $row['topic_id'];
	$word_count = $row['word_count'];
	
	$stmt = $db->prepare('SELECT * FROM topic_words WHERE topic_id = ? LIMIT 30');
	$stmt->bindParam(1, $topic_id, PDO::PARAM_INT);
	$stmt->execute();
	foreach ($stmt->fetchAll() as $row) {
	  $word_class = (int) log($row['word_total']);
	  array_push($topic_word_list, "<span class='word$word_class'>" . $row['word'] . "</span>");
	}
	print '<div class="topic">['.$word_count.' words] '.join(", ", $topic_word_list).' <div><a href="topicpapers?t='.$topic_id.'&y='.$year.'">More articles</a></div></div>';

  }

  $db = null;

} catch (PDOException $e) {
  print $e->getMessage();
}
?>

</div>

</body>
</html>
