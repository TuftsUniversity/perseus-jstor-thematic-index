<?php
header('Content-Type: text/html; charset=utf-8');

$topic_id = $_GET['t'];

header('Content-Type: text/html; charset=utf-8');

if (! is_numeric($topic_id) || $topic_id < 0 || $topic_id > 299) {
  $topic_id = 0;
}

$FIRST_YEAR = 1880;

$decade_ticks = array();
for ($i = 0; $i < 126; $i++) {
  $year = $FIRST_YEAR + $i;
  if (($year % 100) == 0) {
	array_push($decade_ticks, 0.5);
  }
  else if (($year % 50) == 0) {
	array_push($decade_ticks, 0.3);
  }
  else if (($year % 10) == 0) {
	array_push($decade_ticks, 0.1);
  }
  else {
	array_push($decade_ticks, 0);
  }
}

?><html>
<head>
<title>Thematic Index of Classics in JStor</title>
<script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="jquery.sparkline.min.js"></script>
    <script type="text/javascript">
    $(function() {
		$('.sparkline').sparkline([<?php print join(",", $decade_ticks); ?>], {type: 'bar', zeroColor: '#fff', barColor: '#ddd'});
		$('.sparkline').sparkline('html', {composite: 'true'});
	  });
    </script>
<link rel="stylesheet" type="text/css" href="jstor.css"/>
</head>
<body>

<div class="header"><a href="index.php">Thematic Index of Classics in JStor</a></div>

<div class="topics">

<?php
try {

  $db = new PDO("sqlite:jstor.t300.db");

?>
<div class='side_box'>

<h3>Trends by journal</h3>

<p>
This page shows the number of words related to this theme published 
in journals archived by JStor. Note that the scale of the "y-axis" of these
  plots is not constant across journals, and many themes are concentrated
on a small number of journals.
</p>

<h3>Articles by Year</h3>


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
  $stmt = $db->prepare('SELECT * FROM journal_topic_year_counts WHERE topic_id = :topic and year >= 1880 and year < 2007 ORDER BY journal, year');
  $stmt->bindParam(':topic', $topic_id, PDO::PARAM_INT);
  $stmt->execute();
  foreach ($stmt->fetchAll() as $row) {
	$journal = $row['journal'];
	if (! $journal_year_counts[$journal]) {
      $journal_year_counts[$journal] = array();
    }
	$year = $row['year'];
	$count = $row['word_count'];
	$offset = $year - $FIRST_YEAR;
	
	$journal_year_counts[$journal][$offset] = $count;
	$year_counts[$offset] += $count;
	$journal_counts[$journal] += $count;
	$total += $count;
  }

  $offsets = array_keys($decade_ticks);
  sort($offsets);

  arsort($journal_counts);

  foreach ($journal_counts as $journal => $journal_count) {
	$year_proportions = array();
	foreach ($offsets as $i) {
	  array_push($year_proportions, $journal_year_counts[$journal][$i]);
	}

	$percent = sprintf("%.1f", 100 * $journal_count / $total);

	print "<div class='journal'><span>$journal</span> ($percent%)\n";
	print "<div class='timeseries'>$FIRST_YEAR <span class='sparkline' values='" . join(",", $year_proportions) . "'></span> 2006</div><div class='clearing'></div>";
   	print "</div>";
  }

  $db = null;

} catch (PDOException $e) {
  print $e->getMessage();
}
?>

</div>

</body>
</html>
