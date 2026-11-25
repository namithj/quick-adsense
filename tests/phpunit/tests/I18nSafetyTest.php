<?php
require_once dirname( __DIR__ ) . '/includes/class-qa-plugin-hook-capture.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-notice-recorder.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-browser-form.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-snapshot-store.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-file-sentinel.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-ajax-die.php';
require_once dirname( __DIR__ ) . '/includes/class-qa-user-config-runner.php';
require_once dirname( __DIR__, 2 ) . '/user-configs/loader.php';

/**
 * F007 safety net (contract C-4.1 and C-5.2): translating every Quick Adsense
 * string must not change what the plugin renders or parses.
 *
 * A gettext filter rewrites every string of the "quick-adsense" text domain
 * (a fake de_DE with non-ASCII wrapping) and the R00 scenarios of three
 * configurations then run against the frozen snapshots. Rendered output,
 * widget registry and stored settings must be identical. The only output that
 * may differ is the quicktag button title (the sixth argument of
 * QTags.addButton); button ids, labels and inserted markers must stay ASCII
 * and identical.
 *
 * Run with bin/test-user-configs.sh (composer test:configs). Under the default
 * bootstrap this test is skipped.
 *
 * @group user-configs
 */
class QuickAdsenseI18nSafetyTest extends WP_UnitTestCase {

	/**
	 * Saved global state, restored after every test.
	 *
	 * @var array
	 */
	private $globals_backup = [];

	/**
	 * Skip unless the guarded user-config bootstrap is in use.
	 */
	public static function set_up_before_class() {
		if ( ! defined( 'QA_USER_CONFIGS_BOOTSTRAP' ) ) {
			self::markTestSkipped( 'Run the user-config group with bin/test-user-configs.sh (composer test:configs); it needs the guarded bootstrap.' );
		}
		parent::set_up_before_class();
		if ( 'direct' === apply_filters( 'filesystem_method', 'direct', [], '', false ) || defined( 'FS_METHOD' ) ) {
			throw new RuntimeException( 'The filesystem_method guard is not active: QuickAdsense\\FileHandler could write the real ads.txt. Refusing to run.' );
		}
	}

	/**
	 * Expose the core test factory to the runner.
	 *
	 * @return WP_UnitTest_Factory
	 */
	public function qa_factory() {
		return self::factory();
	}

	public function set_up() {
		parent::set_up();
		$this->globals_backup = QA_User_Config_Runner::backup_globals();
	}

	public function tear_down() {
		QA_User_Config_Runner::restore_globals( $this->globals_backup );
		parent::tear_down();
	}

	/**
	 * Wrap a translation so that it can never equal its English source.
	 *
	 * @param string $text Translation.
	 * @return string
	 */
	public static function fake_translate( $text ) {
		return "\u{00DC}" . $text . "\u{00DF}";
	}

	/**
	 * Fake locale filter for plain strings.
	 *
	 * @param string $translation Translation.
	 * @param string $text        Source.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public function filter_gettext( $translation, $text, $domain ) {
		unset( $text );
		return 'quick-adsense' === $domain ? self::fake_translate( $translation ) : $translation;
	}

	/**
	 * Fake locale filter for strings with a context.
	 *
	 * @param string $translation Translation.
	 * @param string $text        Source.
	 * @param string $context     Context.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public function filter_gettext_with_context( $translation, $text, $context, $domain ) {
		unset( $text, $context );
		return 'quick-adsense' === $domain ? self::fake_translate( $translation ) : $translation;
	}

	/**
	 * Configurations with widgets, many slots and quicktags.
	 *
	 * @return array<string, array{string}>
	 */
	public function config_provider() {
		$rows = [];
		foreach ( [ '05-all-slots-all-positions', '06-random-and-quicktags', '09-widgets-only' ] as $slug ) {
			$rows[ $slug ] = [ $slug ];
		}
		return $rows;
	}

	/**
	 * Split the quicktag section out of an admin-steps file and mask the button titles.
	 *
	 * @param string $text admin-steps.txt contents.
	 * @return array{0:string,1:string} Text without the quicktag section, masked quicktag section.
	 */
	private static function split_quicktags( $text ) {
		$parts = preg_split( '/^(== [a-z-]+ ==)$/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$rest  = '';
		$tags  = '';
		$total = count( $parts );
		for ( $i = 1; $i < $total; $i += 2 ) {
			if ( '== quicktags ==' === $parts[ $i ] ) {
				$tags = $parts[ $i + 1 ];
			} else {
				$rest .= $parts[ $i ] . $parts[ $i + 1 ];
			}
		}
		return [ $rest, $tags ];
	}

	/**
	 * Run a config under the fake locale; everything except the quicktag titles must match.
	 *
	 * @dataProvider config_provider
	 *
	 * @param string $slug Config slug.
	 */
	public function test_fake_locale_changes_nothing_that_is_parsed( $slug ) {
		$store = new QA_Snapshot_Store( QA_User_Config_Loader::dir() . '/snapshots' );
		add_filter( 'gettext', [ $this, 'filter_gettext' ], 10, 3 );
		add_filter( 'gettext_with_context', [ $this, 'filter_gettext_with_context' ], 10, 4 );

		// The filter really rewrites the plugin's strings.
		$this->assertSame( self::fake_translate( 'Tools' ), __( 'Tools', 'quick-adsense' ) );
		$this->assertSame( 'Tools', __( 'Tools', 'default' ) );

		$runner = new QA_User_Config_Runner( $this, $this->globals_backup );
		$result = $runner->run( QA_User_Config_Loader::load( $slug ) );

		$problems = $result['failures'];
		$tags_run = 0;
		foreach ( $result['outputs'] as $relative => $text ) {
			if ( '/admin-steps.txt' === substr( $relative, -16 ) ) {
				$expected                              = (string) file_get_contents( QA_User_Config_Loader::dir() . '/snapshots/' . $relative ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local snapshot file.
				list( $rest_actual, $tags_actual )     = self::split_quicktags( $text );
				list( $rest_expected, $tags_expected ) = self::split_quicktags( $expected );
				if ( $rest_actual !== $rest_expected ) {
					$problems[] = $relative . ': admin steps differ outside the quicktag section.';
				}
				$tags_run += $this->assert_quicktag_markers( $tags_actual, $tags_expected, $relative, $problems );
				continue;
			}
			$problem = $store->check( $relative, $text );
			if ( null !== $problem ) {
				$problems[] = $problem;
			}
		}

		if ( '06-random-and-quicktags' === $slug ) {
			$this->assertGreaterThan( 0, $tags_run, 'No quicktag buttons were produced; the quicktag check did not run.' );
		}
		$this->addToAssertionCount( $result['checks'] + count( $result['outputs'] ) );
		$this->assertSame( [], array_slice( $problems, 0, 8 ), $slug . ': output changed under a translated locale (C-4.1 / C-5.2).' );
	}

	/**
	 * Check that every quicktag button keeps its id, label and ASCII marker.
	 *
	 * @param string   $actual   Quicktag section under the fake locale.
	 * @param string   $expected Quicktag section of the snapshot.
	 * @param string   $label    Snapshot path, for messages.
	 * @param string[] $problems Problem list, appended to.
	 * @return int Number of buttons checked.
	 */
	private function assert_quicktag_markers( $actual, $expected, $label, array &$problems ) {
		$pattern = '/QTags\.addButton\("([^"]+)", "([^"]+)", "((?:[^"\\\\]|\\\\.)*)", "", "", "([^"]*)", 201\);/';
		preg_match_all( $pattern, $actual, $got, PREG_SET_ORDER );
		preg_match_all( $pattern, $expected, $want, PREG_SET_ORDER );
		if ( count( $got ) !== count( $want ) ) {
			$problems[] = $label . ': the number of quicktag buttons changed.';
			return 0;
		}
		foreach ( $got as $index => $button ) {
			// Id, label and inserted value are identical to the snapshot, and the value is plain ASCII.
			foreach ( [ 1, 2, 3 ] as $part ) {
				if ( $button[ $part ] !== $want[ $index ][ $part ] ) {
					$problems[] = $label . ': quicktag button ' . $button[1] . ' changed under the fake locale.';
				}
			}
			$this->assertMatchesRegularExpression( '/^\\\\n<!--[A-Za-z0-9]+-->\\\\n$/', $button[3] );
			$this->assertSame( 1, preg_match( '/^[\x20-\x7E]*$/', $button[1] . $button[2] . $button[3] ), 'Quicktag id, label or marker is not ASCII.' );
			// The title is the one thing that may be translated, and here it is.
			$this->assertContains( $button[4], [ $want[ $index ][4], self::fake_translate( $want[ $index ][4] ) ], 'Quicktag title is neither the English nor the translated title.' );
			if ( 'quick_adsense_quicktag_randomads' === $button[1] ) {
				$this->assertSame( self::fake_translate( 'Random Ads' ), $button[4], 'The quicktag title was not translated, so the test proves nothing.' );
			}
		}
		return count( $got );
	}
}
