<?php
header('Content-Type: text/html; charset=utf-8');

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

<div class="header">Thematic Index of Classics in JStor</div>

<div class="side_box">
<h3>About the Index</h3>
<p>
	This site is an automatically generated index of themes in a collection of more than 130,000 research articles
  archived in <a href="http://www.jstor.org">JStor</a>. For each theme, you can browse articles 
  associated with that theme, with links directly to the text of the articles in JStor.
  You can also view temporal trends in publication for the theme, organized by journal.
</p>

<p>
Each theme is represented by assigning a weight to every word in the vocabulary of the corpus. The 50 highest weighted words are shown. Some 
  themes are better-estimated than others. Words that are shown in full-size and black are likely to be meaningful, while smaller 
words in lighter shades may be random artifacts of the model.
</p>

<p>
The time-series plot shows the proportion of the words in the corpus in a given year that are assigned to a theme.
Gray bars show decades. The y-axis is not comparable between themes.
</p>

<p>
The work presented here was developed by David Mimno under the
Cybereditions Project, an effort led by the Perseus Project at Tufts
University and funded by the Mellon Foundation.
</p>
</div>

<div class="topics">

<?php
try {

  $db = new PDO("sqlite:jstor.t300.db");

  $topic_word_lists = array();
  $NUM_WORDS = 50;

  $result = $db->query('SELECT * FROM topic_words');
  foreach ($result as $row) {
    $topic = $row['topic_id'];
    if (! $topic_word_lists[$topic]) {
	  $topic_word_lists[$topic] = array();
    }
	if (count($topic_word_lists[$topic]) < $NUM_WORDS) {
	  $word_class = (int) log($row['word_total']);
	  array_push($topic_word_lists[$topic], "<span class='word$word_class'>" . $row['word'] . "</span>");
	}
  }

  $topic_year_counts = array();
  $year_counts = array();
  $result = $db->query('SELECT * FROM topic_years WHERE year >= 1880 and year < 2007 ORDER BY topic_id, year');
  foreach ($result as $row) {
	$topic = $row['topic_id'];
	if (! $topic_year_counts[$topic]) {
      $topic_year_counts[$topic] = array();
    }
	$year = $row['year'];
	$count = $row['word_total'];
	$offset = $year - $FIRST_YEAR;
	
	$topic_year_counts[$topic][$offset] = $count;
	$year_counts[$offset] += $count;
  }

  $offsets = array_keys($year_counts);
  sort($offsets);

  foreach ($topic_word_lists as $topic => $topic_words) {
	$year_proportions = array();
	foreach ($offsets as $i) {
	  array_push($year_proportions, $topic_year_counts[$topic][$i] / $year_counts[$i]);
	}

	print "<div class='topic'><div class='actions'><div><a href='topicpapers.php?t=$topic&y=2006'>List papers</a></div><div><a href='topicjournals.php?t=$topic'>Show journal trends</a></div></div>" . join(", ", $topic_words);
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
