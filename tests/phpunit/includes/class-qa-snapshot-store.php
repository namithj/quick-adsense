<?php
/**
 * Golden-master snapshot files for the user-config regression suite.
 *
 * - A missing snapshot fails. QA_UPDATE_SNAPSHOTS=1 creates it.
 * - A mismatch fails with a unified diff.
 * - QA_UPDATE_SNAPSHOTS=1 is refused in CI.
 */
class QA_Snapshot_Store {

	/**
	 * Root directory of the snapshots.
	 *
	 * @var string
	 */
	private $root;

	/**
	 * Whether QA_UPDATE_SNAPSHOTS=1 is in effect.
	 *
	 * @var bool
	 */
	private $update;

	/**
	 * Snapshot paths (relative to the root) checked during this run.
	 *
	 * @var string[]
	 */
	private $seen = [];

	/**
	 * Number of files written in update mode.
	 *
	 * @var int
	 */
	private $written = 0;

	/**
	 * Constructor.
	 *
	 * @param string $root Snapshot root directory.
	 */
	public function __construct( $root ) {
		$this->root   = rtrim( $root, '/' );
		$this->update = '1' === getenv( 'QA_UPDATE_SNAPSHOTS' );
		if ( $this->update && ( getenv( 'CI' ) || getenv( 'GITHUB_ACTIONS' ) ) ) {
			throw new RuntimeException( 'QA_UPDATE_SNAPSHOTS=1 is forbidden in CI. Regenerate snapshots locally and list the diff under "Intended snapshot changes" in the release plan.' );
		}
	}

	/**
	 * Is update mode on?
	 *
	 * @return bool
	 */
	public function is_update_mode() {
		return $this->update;
	}

	/**
	 * Number of snapshot files written (update mode only).
	 *
	 * @return int
	 */
	public function written() {
		return $this->written;
	}

	/**
	 * Compare actual text with the stored snapshot.
	 *
	 * @param string $relative Path relative to the root, for example "01-x/single-long.txt".
	 * @param string $actual   The text produced by this run.
	 * @return string|null A failure message, or null when the snapshot matches.
	 */
	public function check( $relative, $actual ) {
		$this->seen[] = $relative;
		$path         = $this->root . '/' . $relative;

		if ( $this->update ) {
			$existing = is_file( $path ) ? file_get_contents( $path ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( $existing !== $actual && ( null === $existing || self::normalise_core_formatting( $existing ) !== self::normalise_core_formatting( $actual ) ) ) {
				if ( ! is_dir( dirname( $path ) ) ) {
					mkdir( dirname( $path ), 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
				}
				file_put_contents( $path, $actual ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				++$this->written;
			}
			return null;
		}

		if ( ! is_file( $path ) ) {
			return 'Missing snapshot ' . $relative . '. Run with QA_UPDATE_SNAPSHOTS=1 to create it.';
		}
		$expected = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( $expected === $actual ) {
			return null;
		}
		if ( self::normalise_core_formatting( $expected ) === self::normalise_core_formatting( $actual ) ) {
			return null;
		}
		return 'Snapshot mismatch: ' . $relative . self::changed_sections( $expected, $actual ) . "\n" . self::diff( $expected, $actual );
	}

	/**
	 * Find snapshot files in a directory that this run did not produce.
	 *
	 * In update mode they are deleted. Otherwise they are reported.
	 *
	 * @param string $relative_dir Directory relative to the root ("" for the root).
	 * @param bool   $recursive    Whether to descend into sub-directories.
	 * @return string[] Stale paths relative to the root.
	 */
	public function stale( $relative_dir, $recursive = false ) {
		$dir   = $this->root . ( '' === $relative_dir ? '' : '/' . $relative_dir );
		$stale = [];
		if ( ! is_dir( $dir ) ) {
			return $stale;
		}
		$iterator = $recursive
			? new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) )
			: new FilesystemIterator( $dir, FilesystemIterator::SKIP_DOTS );
		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$relative = ltrim( substr( str_replace( '\\', '/', $file->getPathname() ), strlen( $this->root ) ), '/' );
			if ( ! in_array( $relative, $this->seen, true ) ) {
				$stale[] = $relative;
			}
		}
		sort( $stale );
		if ( $this->update ) {
			foreach ( $stale as $relative ) {
				unlink( $this->root . '/' . $relative ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			}
			return [];
		}
		return $stale;
	}

	/**
	 * Level out two WordPress-core formatting details that changed in WP 7.2.
	 *
	 * 1. The attribute order of core's sidebar wrapper (before_widget), for
	 *    example id-then-class versus class-then-id.
	 * 2. The newline core used to put before inline script data, which shows up
	 *    as a blank line right after a "== name ==" section header.
	 *
	 * Neither is visitor-visible and neither is produced by the plugin. Nothing
	 * else is normalised.
	 *
	 * @param string $text Snapshot or actual text.
	 * @return string
	 */
	private static function normalise_core_formatting( $text ) {
		$text = preg_replace_callback(
			'/<([a-zA-Z][a-zA-Z0-9-]*)((?:\s+[a-zA-Z_:-]+="[^"]*")+)\s*>/',
			static function ( $m ) {
				if ( ! preg_match_all( '/([a-zA-Z_:-]+)="([^"]*)"/', $m[2], $found, PREG_SET_ORDER ) ) {
					return $m[0];
				}
				$attrs = [];
				foreach ( $found as $pair ) {
					$attrs[ $pair[1] ] = $pair[2];
				}
				if ( ! isset( $attrs['id'], $attrs['class'] ) || 0 !== strpos( $attrs['class'], 'widget ' ) ) {
					return $m[0];
				}
				ksort( $attrs );
				$out = '<' . $m[1];
				foreach ( $attrs as $name => $value ) {
					$out .= ' ' . $name . '="' . $value . '"';
				}
				return $out . '>';
			},
			$text
		);
		return preg_replace( '/^(== [a-z-]+ ==\n)\n/m', '$1', $text );
	}

	/**
	 * Name the "== section ==" blocks that differ.
	 *
	 * @param string $expected Stored text.
	 * @param string $actual   New text.
	 * @return string
	 */
	private static function changed_sections( $expected, $actual ) {
		$old = self::sections( $expected );
		$new = self::sections( $actual );
		if ( ! $old && ! $new ) {
			return '';
		}
		$changed = [];
		foreach ( array_unique( array_merge( array_keys( $old ), array_keys( $new ) ) ) as $name ) {
			if ( ( $old[ $name ] ?? null ) !== ( $new[ $name ] ?? null ) ) {
				$changed[] = $name;
			}
		}
		return $changed ? ' (changed: ' . implode( ', ', $changed ) . ')' : '';
	}

	/**
	 * Split snapshot text into its "== name ==" sections.
	 *
	 * @param string $text Snapshot text.
	 * @return array<string, string>
	 */
	private static function sections( $text ) {
		$parts = preg_split( '/^== ([a-z-]+) ==\n/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = [];
		$total = count( $parts );
		for ( $i = 1; $i + 1 < $total; $i += 2 ) {
			$out[ $parts[ $i ] ] = $parts[ $i + 1 ];
		}
		return $out;
	}

	/**
	 * Make carriage returns and other invisible bytes visible, then diff.
	 *
	 * @param string $expected Stored text.
	 * @param string $actual   New text.
	 * @return string
	 */
	public static function diff( $expected, $actual ) {
		$differ = new SebastianBergmann\Diff\Differ(
			new SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder( "--- expected (snapshot)\n+++ actual (this run)\n" )
		);
		$diff   = $differ->diff( self::visible( $expected ), self::visible( $actual ) );
		$lines  = explode( "\n", $diff );
		if ( count( $lines ) > 80 ) {
			$omitted = count( $lines ) - 80;
			$lines   = array_slice( $lines, 0, 80 );
			$lines[] = '... (' . $omitted . ' more diff lines omitted)';
		}
		return implode( "\n", $lines );
	}

	/**
	 * Show CR as a visible symbol and cap very long lines.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function visible( $text ) {
		$text = str_replace( "\r", "\u{240D}", $text );
		return preg_replace_callback(
			'/^.{1000,}$/m',
			static function ( $m ) {
				return substr( $m[0], 0, 1000 ) . ' ...(' . ( strlen( $m[0] ) - 1000 ) . ' more bytes)';
			},
			$text
		);
	}
}
