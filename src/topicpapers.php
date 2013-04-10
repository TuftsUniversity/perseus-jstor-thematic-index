<?php
header('Content-Type: text/html; charset=utf-8');

$topic_id = $_GET['t'];
$year = $_GET['y'];

header('Content-Type: text/html; charset=utf-8');

if (! is_numeric($topic_id) || $topic_id < 0 || $topic_id > 299) {
  $topic_id = 0;
}
if (! is_numeric($year) || $year < 1869 || $year > 2007) {
  $year = 2000;
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

?>
<div class='side_box'><h3>Articles by Year</h3>
<p>
  This page shows papers published in <?php print($year); ?> that contain words associated with
  the automatically identified theme characterized by the words shown to the left.
</p>

<?php

  $stmt = $db->prepare('SELECT * FROM topic_articles_per_year WHERE topic_id = ? ORDER BY year DESC');
  $stmt->bindParam(1, $topic_id, PDO::PARAM_INT);
  $stmt->execute();
  foreach ($stmt->fetchAll() as $row) {
	print "<a href='topicpapers.php?t=$topic_id&y=" . $row['year'] . "'>" . $row['year'] . " (" . $row['article_count'] .")</a>\n";
	if ($row['year'] % 3 == 1) { print "<br/>"; }
  }
?>
<p>
The work presented here was developed by David Mimno under the
  Cybereditions Project, an effort led by the Perseus Project at Tufts
University and funded by the Mellon Foundation.
</p>
</div>
<?php
  
  $topic_word_list = array();
  $NUM_WORDS = 50;

  $stmt = $db->prepare('SELECT * FROM topic_words WHERE topic_id = ? LIMIT 50');
  $stmt->bindParam(1, $topic_id, PDO::PARAM_INT);
  $stmt->execute();
  foreach ($stmt->fetchAll() as $row) {
	$word_class = (int) log($row['word_total']);
	array_push($topic_word_list, "<span class='word$word_class'>" . $row['word'] . "</span>");
  }
  print '<div class="topic">'.join(", ", $topic_word_list).'</div>';

  $journal_year_counts = array();
  $journal_counts = array();
  $year_counts = array();
  $total = 0;
  $stmt = $db->prepare('select a.*, atc.* from article_topic_counts atc, articles a where atc.topic_id = :topic and atc.doi = a.doi and a.year = :year order by atc.word_count DESC');
  $stmt->bindParam(':topic', $topic_id, PDO::PARAM_INT);
  $stmt->bindParam(':year', $year, PDO::PARAM_INT);
  $stmt->execute();
  
  $current_year = 0;

  foreach ($stmt->fetchAll() as $row) {
	$doi = $row['doi'];

	$year = $row['year'];
	if ($year != $current_year) {
	  print "<h1>$year</h1>\n";
	  $current_year = $year;
	}

	$ref = "";
	if ($row['title'] != '') {
	  $ref .= "<span class=\"booktitle\">" . $row['title'] . "</span>. ";
	}
	if ($row['author'] != '') {
	  $ref .= $row['author'] . ". ";
	}
	if ($row['journal'] != '') {
	  $ref .= $row['journal'] . ". ";
	}
	if ($row['display_date'] != '') {
	  $ref .= "(" . $row['display_date'] . "), ";
	}
	if ($row['pages'] != '') {
	  $ref .= "pp. " . $row['pages'];
	}

?>
<div class="article">
  <div class="bibdata">
<?php print($ref) ?>
   <a href="papertopics.php?d=<?php print($doi) ?>">List themes</a>
   <a class="jstorref" href="http://www.jstor.org/stable/<?php print($doi) ?>">Full text</a> (<?php print($row['word_count']) ?> theme words)
  </div>
</div>
<?php
  }

  $db = null;

} catch (PDOException $e) {
  print $e->getMessage();
}
?>

</div>

</body>
</html>
