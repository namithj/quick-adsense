<?php
/**
 * Named post bodies for the user-config regression suite.
 *
 * Bodies are final HTML, as they would reach the plugin's the_content filter
 * after wpautop. The "more" body carries the {ID} placeholder, which the
 * runner replaces with the real post ID.
 *
 * @return array<string, string> Fixture name => post body.
 */

$qa_text = static function ( $length, $seed ) {
	$words = [ 'alpha', 'bravo', 'charlie', 'delta', 'echo', 'foxtrot', 'golf', 'hotel', 'india', 'juliet', 'kilo', 'lima' ];
	$out   = [];
	$len   = 0;
	$i     = $seed;
	while ( $len < $length ) {
		$word  = $words[ $i % count( $words ) ];
		$out[] = $word;
		$len  += strlen( $word ) + 1;
		$i    += 5;
	}
	return ucfirst( implode( ' ', $out ) ) . '.';
};

$qa_lengths = [ 45, 120, 300, 60, 500, 80, 220, 40, 350, 90, 150, 70 ];

// The twelve paragraphs of the "long" body, as <p> elements.
$qa_paras = [];
foreach ( $qa_lengths as $qa_i => $qa_len ) {
	$qa_paras[ $qa_i + 1 ] = '<p>P' . ( $qa_i + 1 ) . ' ' . $qa_text( $qa_len, $qa_i ) . '</p>';
}

// Build "long": 12 paragraphs, two <h2> headings, and a <ul>.
$qa_long_parts = [
	$qa_paras[1],
	$qa_paras[2],
	$qa_paras[3],
	$qa_paras[4],
	'<h2>First heading</h2>',
	$qa_paras[5],
	$qa_paras[6],
	"<ul>\n<li>List item one</li>\n<li>List item two</li>\n<li>List item three</li>\n</ul>",
	$qa_paras[7],
	$qa_paras[8],
	$qa_paras[9],
	'<h2>Second heading</h2>',
	$qa_paras[10],
	$qa_paras[11],
	$qa_paras[12],
];

/**
 * Join content parts the way wpautop separates them.
 *
 * @param string[] $parts Content parts.
 * @return string
 */
$qa_join = static function ( array $parts ) {
	return implode( "\n", $parts );
};

/**
 * Return "long" with a marker paragraph inserted after the Nth <p> paragraph.
 *
 * @param string[] $parts  The long body parts.
 * @param array    $marks  Map of paragraph number => marker comment.
 * @param array    $paras  Paragraph element lookup (number => element).
 * @return string
 */
$qa_with_markers = static function ( array $parts, array $marks, array $paras ) use ( $qa_join ) {
	$out = [];
	foreach ( $parts as $part ) {
		$out[] = $part;
		$num   = array_search( $part, $paras, true );
		if ( false !== $num && isset( $marks[ $num ] ) ) {
			foreach ( (array) $marks[ $num ] as $mark ) {
				$out[] = '<p><!--' . $mark . '--></p>';
			}
		}
	}
	return $qa_join( $out );
};

$qa_posts = [];

$qa_posts['long']        = $qa_join( $qa_long_parts );
$qa_posts['short']       = '<p>The only paragraph of the short post.</p>';
$qa_posts['two-paras']   = $qa_join( [ '<p>First of two paragraphs.</p>', '<p>Second of two paragraphs.</p>' ] );
$qa_posts['upper-p']     = $qa_join(
	[
		'<P>Upper case one ' . $qa_text( 60, 1 ) . '</P>',
		'<P>Upper case two ' . $qa_text( 120, 2 ) . '</P>',
		'<P>Upper case three ' . $qa_text( 90, 3 ) . '</P>',
		'<P>Upper case four ' . $qa_text( 200, 4 ) . '</P>',
		'<P>Upper case five ' . $qa_text( 50, 5 ) . '</P>',
		'<P>Upper case six ' . $qa_text( 80, 6 ) . '</P>',
	]
);
$qa_posts['empty-paras'] = $qa_join(
	[
		'<p>Before the empty paragraphs ' . $qa_text( 80, 1 ) . '</p>',
		'<p></p>',
		'<p>Between ' . $qa_text( 120, 2 ) . '</p>',
		'<p>&nbsp;</p>',
		'<p>After ' . $qa_text( 100, 3 ) . '</p>',
		'<p></p>',
		'<p>Last ' . $qa_text( 60, 4 ) . '</p>',
	]
);
$qa_posts['images']      = $qa_join(
	[
		'<p>Intro to the image post ' . $qa_text( 60, 1 ) . '</p>',
		'<p><img src="https://example.org/wp-content/uploads/qa-1.jpg" alt="one" width="300" height="200" /></p>',
		'<p>Between one and two ' . $qa_text( 50, 2 ) . '</p>',
		'<p><a href="https://example.org/qa-2-full.jpg"><img src="https://example.org/wp-content/uploads/qa-2.jpg" alt="two" width="300" height="200" /></a></p>',
		'<p>Between two and three ' . $qa_text( 50, 3 ) . '</p>',
		'[caption id="attachment_3" align="aligncenter" width="300"]<img src="https://example.org/wp-content/uploads/qa-3.jpg" alt="three" width="300" height="200" /> Caption three[/caption]',
		'<p>Between three and four ' . $qa_text( 50, 4 ) . '</p>',
		'<p><IMG SRC="https://example.org/wp-content/uploads/qa-4.jpg" ALT="four" WIDTH="300" HEIGHT="200"></p>',
		'<p>Between four and five ' . $qa_text( 50, 5 ) . '</p>',
		'<p><a href="https://example.org/qa-5-full.jpg"><IMG src="https://example.org/wp-content/uploads/qa-5.jpg" alt="five" width="300" height="200"></A></p>',
		'<p>Closing paragraph ' . $qa_text( 70, 6 ) . '</p>',
	]
);
$qa_posts['more']        = $qa_join(
	[
		'<p>Before the more tag ' . $qa_text( 120, 1 ) . '</p>',
		'<p>Still before the more tag ' . $qa_text( 80, 2 ) . '</p>',
		'<p><span id="more-{ID}"></span></p>',
		'<p>After the more tag ' . $qa_text( 150, 3 ) . '</p>',
		'<p>Final paragraph ' . $qa_text( 90, 4 ) . '</p>',
	]
);

$qa_block_paras = [];
for ( $qa_i = 1; $qa_i <= 6; $qa_i++ ) {
	$qa_block_paras[] = "<!-- wp:paragraph -->\n<p>Block paragraph " . $qa_i . ' ' . $qa_text( 60 + 20 * $qa_i, $qa_i ) . "</p>\n<!-- /wp:paragraph -->";
}
$qa_posts['blocks'] = implode( "\n\n", $qa_block_paras );

$qa_posts['classic-block-markers'] = $qa_join(
	[
		'<p>Before the HTML block ' . $qa_text( 90, 1 ) . '</p>',
		'<!-- wp:html --><!--Ads3--><!-- /wp:html -->',
		'<p>Between the HTML blocks ' . $qa_text( 110, 2 ) . '</p>',
		'<!-- wp:html --><!--OffEnd--><!-- /wp:html -->',
		'<p>After the HTML blocks ' . $qa_text( 80, 3 ) . '</p>',
	]
);

// Marker posts: "long" plus a marker paragraph after paragraph 2.
foreach ( [ 1, 5, 10 ] as $qa_n ) {
	$qa_posts[ 'm-ads' . $qa_n ] = $qa_with_markers( $qa_long_parts, [ 2 => 'Ads' . $qa_n ], $qa_paras );
}
$qa_posts['m-rndads'] = $qa_with_markers(
	$qa_long_parts,
	[
		3 => 'RndAds',
		8 => 'RndAds',
	],
	$qa_paras
);
foreach ( [ 'NoAds', 'OffAds', 'OffDef', 'OffWidget', 'OffBegin', 'OffMiddle', 'OffEnd', 'OffAfMore', 'OffBfLastPara' ] as $qa_marker ) {
	$qa_posts[ 'm-' . strtolower( $qa_marker ) ] = $qa_with_markers( $qa_long_parts, [ 2 => $qa_marker ], $qa_paras );
}
$qa_posts['m-mixed'] = $qa_with_markers(
	$qa_long_parts,
	[
		1 => 'OffDef',
		2 => 'Ads2',
		5 => 'RndAds',
		6 => 'OffWidget',
	],
	$qa_paras
);

return $qa_posts;
