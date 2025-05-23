<?php
namespace ILJ\Backend\MenuPage;

use ILJ\Backend\AdminMenu;
use ILJ\Core\Options\TaxonomyWhitelist;
use ILJ\Core\Options\Whitelist;
use ILJ\Helper\Export;
use ILJ\Helper\Options;
use ILJ\Type\KeywordList;

/**
 * The tools menu page
 *
 * Shows import/export actions and other tools for automating things
 *
 * @package ILJ\Backend\Menupage
 *
 * @since 1.2.0
 */
class Tools extends AbstractMenuPage {


	const ILJ_MENUPAGE_TOOLS_SLUG                       = 'tools';
	const ILJ_MENUPAGE_TOOLS_IMPORT_INTERN_POST         = 'ilj-import-intern-post';
	const ILJ_MENUPAGE_TOOLS_IMPORT_INTERN_TERM         = 'ilj-import-intern-term';
	const ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_POST = 'ilj_filter_menupage_tools_keyword_import_post';
	const ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_TERM = 'ilj_filter_menupage_tools_keyword_import_term';

	/**
	 * Csv Format
	 *
	 * @var   string
	 * @since 1.2.0
	 */
	protected $csvFormats;

	public function __construct() {
		 $this->page_slug = self::ILJ_MENUPAGE_TOOLS_SLUG;
		$this->page_title = __('Import / Export', 'internal-links');
	}

	/**
	 * register
	 *
	 * @return void
	 */
	public function register() {
		$this->addSubMenuPage();

		add_action('current_screen', array($this, 'exportScreen'));
		\ILJ\Helper\Loader::register_script('ilj_tools', ILJ_URL . 'admin/js/ilj_tools.js', array(), ILJ_VERSION);
		wp_localize_script(
			'ilj_tools',
			'ilj_tools',
			array(
				'ajax_url'    => admin_url('admin-ajax.php'),
				'nonce'       => wp_create_nonce('ilj-tools'),
				'translation' => self::getTranslation(),
			)
		);

		$this->addAssets(
			array(
				'tipso'             => ILJ_URL . 'admin/js/tipso.js',
				'ilj_select2'       => ILJ_URL . 'admin/js/select2.js',
				'ilj_menu_settings' => ILJ_URL . 'admin/js/ilj_menu_settings.js',
				'ilj_tools'         => ILJ_URL . 'admin/js/ilj_tools.js',
			),
			array(
				'tipso'             => ILJ_URL . 'admin/css/tipso.css',
				'ilj_menu_settings' => ILJ_URL . 'admin/css/ilj_menu_settings.css',
				'ilj_ui'            => ILJ_URL . 'admin/css/ilj_ui.css',
				'ilj_grid'          => ILJ_URL . 'admin/css/ilj_grid.css',
				'ilj_select2'       => ILJ_URL . 'admin/css/select2.css',
				'ilj_tools'         => ILJ_URL . 'admin/css/ilj_tools.css',
			)
		);
	}

	/**
	 * Returns the frontend translation
	 *
	 * @since  1.2.0
	 * @return array
	 */
	protected static function getTranslation() {
		$translation = array(
			'loading'               => __('Loading', 'internal-links'),
			'error'                 => __('An error occurred.', 'internal-links') . ' ' . __('Please try again.', 'internal-links'),
			'close'                 => __('Close', 'internal-links'),
			'start_import'          => __('Start import', 'internal-links'),
			'cancel_import'         => __('Cancel', 'internal-links'),
			'upload_success'        => __('The upload was successful.', 'internal-links') . ' ' . __('You can now start importing the uploaded data.', 'internal-links'),
			'import_success'        => __('Import completed successfully.', 'internal-links'),
			'upload_error_filesize' => __('The upload exceeds the maximum allowed file size.', 'internal-links'),
		);

		

		return $translation;
	}

	/**
	 * render
	 *
	 * @return void
	 */
	public function render() {
		if (!current_user_can('manage_options')) {
			return;
		}

		echo '<div class="wrap ilj-menu-settings ilj-tools">';

		$this->renderHeadline(__('Tools for data import and export', 'internal-links'));

		echo '	<div class="ilj-row">';
		echo '		<div class="col-6">';

		$this->renderPostbox(
			array(
				'title'   => __('File import', 'internal-links'),
				'content' => $this->getFileImport(),
				'class'   => 'tools',
			)
		);

		$this->renderPostbox(
			array(
				'title'   => __('File export', 'internal-links'),
				'content' => $this->getFileExport(),
				'class'   => 'tools',
			)
		);

		echo '		</div>';
		echo '		<div class="col-6">';

		{
      ?>
			<div>
				<a href="<?php 
      echo esc_url(get_admin_url(null, 'admin.php?billing_cycle=annual&trial=true&page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing'));
      ?>"
					class="ilj-upgrade tools">
					&rsaquo; <?php 
      esc_html_e('Upgrade to Pro now - unlock all features', 'internal-links');
      ?> <span class="dashicons dashicons-unlock"></span>
				</a>
			</div>
			<div class="clear"></div>
			<?php 
  }

		$keyword_import_postbox_class = 'disabled has-icon';
		$before_headline              = '<div class="pro-title"><span class="dashicons dashicons-lock tip" title="' . __('This feature is part of the Pro version', 'internal-links') . '"></span></div>';

		

		$this->renderPostbox(
			array(
				'title'           => __('Import keyword configurations from WordPress', 'internal-links'),
				'content'         => $this->getKeywordImport(),
				'class'           => $keyword_import_postbox_class . ' ' . 'tools',
				'before_headline' => $before_headline,
			)
		);

		echo '		</div>';
		echo '	</div>';
		echo '</div>';
	}

	/**
	 * Generates and returns the html area for file imports
	 *
	 * @since  1.2.0
	 * @return string
	 */
	protected function getFileImport() {
		$output = '';

		$output .= '<div class="wrap">';
		$output .= '<div class="ilj-upload-form" data-file-size="3145728" data-file-type="settings">';
		$output .= sprintf('<h3>%s</h3>', __('Import plugin settings', 'internal-links'));
		$output .= '<form method="post" name="ilj-import-settings">';
		$output .= sprintf('<p class="hint">%s</p>', __('Only valid .json files, as generated from our export, are allowed.', 'internal-links'));
		$output .= '<input type="file" name="async-upload" accept=".json" class="import-file" />';
		$output .= sprintf('<button class="button button-primary">%s</button>', __('Upload file', 'internal-links'));
		$output .= '<div class="ilj-progress"><div class="ilj-progress-bar"></div></div>';
		$output .= '</form>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="divide"></div>';

		$output .= '<div class="wrap">';
		$output .= '<div class="ilj-upload-form" data-file-size="10485760" data-file-type="keywords">';
		$output .= sprintf('<h3>%s</h3>', __('Import keyword configuration from CSV file', 'internal-links'));
		$output .= '<form method="post" name="ilj-import-keywords">';
		$output .= sprintf('<p class="hint">%s</p>', __('The column order and format of the CSV file must correspond to the format of our export.', 'internal-links'));
		$output .= '<input type="file" name="async-upload" accept="text/csv" class="import-file" />';
		$output .= sprintf('<button class="button button-primary">%s</button>', __('Upload file', 'internal-links'));
		$output .= '<div class="ilj-progress"><div class="ilj-progress-bar"></div></div>';
		$output .= '</form>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Generates and returns the html area for file exports
	 *
	 * @since  1.2.0
	 * @return string
	 */
	protected function getFileExport() {
		$output = '';

		$output .= '<div class="wrap">';
		$output .= sprintf('<h3>%s</h3>', __('Export plugin settings', 'internal-links'));
		$output .= sprintf('<button class="button button-primary ilj-export" data-export="settings">%s</button>', __('Export', 'internal-links'));
		$output .= '<span class="spinner"></span>';
		$output .= '<div class="clear"></div>';
		$output .= '</div>';

		$output .= '<div class="divide"></div>';

		$output .= '<div class="wrap">';
		$output .= sprintf('<h3>%s</h3>', __('Export keyword configurations as CSV File', 'internal-links'));
		$output .= sprintf('<p class="hint">%s</p>', __('With this feature all linkable content can be exported.', 'internal-links'));
		$output .= '<div class="ilj-row">';

		$output .= '<div class="col-6">';
		$output .= __('Export empty configurations', 'internal-links');
		$output .= '</div>';

		$output .= '<div class="col-6">';
		$output .= Options::getToggleField('ilj-export-empty', 'checked="checked"');
		$output .= '</div>';
		
		$output .= '</div>';

		$output .= '<div class="ilj-row">';
		$output .= '<div class="col-6">';
		/* translators: %s: Additional Columns */
		$output .= sprintf(__('Include additional columns (%s)', 'internal-links'), Export::ILJ_ADDITIONAL_COLUMNS);
		$output .= '</div>';

		$output .= '<div class="col-1">';
		$output .= Options::getToggleField('ilj-export-additional-columns', '');
		$output .= '</div>';
		
		$output .= '<div class="col-4">';
		$output .= '<span class="hint">' . __('These columns will be ignored during import', 'internal-links') . '</span>';
		$output .= '</div>';

		$output .= '</div>';

		$output .= '<div class="ilj-row">';

		$output .= sprintf('<button class="button button-primary ilj-export" data-export="keywords">%s</button>', __('Export', 'internal-links'));
		$output .= '</div>';
		$output .= '<span class="spinner"></span>';
		$output .= '<div class="clear"></div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Generates and returns the html area for keyword imports from internal sources
	 *
	 * @since  1.2.0
	 * @return string
	 */
	protected function getKeywordImport() {
		 $output = '';

		$output .= '<div class="wrap">';
		$output .= sprintf('<h3>%s</h3>', __('Import for posts and pages from internal resources', 'internal-links'));

		$output          .= '<form method="post" name="ilj-import-keywords-post" class="ilj-import-keywords" data-type-import="post">';
		$output          .= '<div class="ilj-row">';
		$output          .= '<div class="col-6">';
		$output          .= __('Types to apply import', 'internal-links');
		$output          .= '</div>';
		$output          .= '<div class="col-6">';
		$post_types_input = '<select name="ilj-import-intern-type" id="ilj-import-intern-type-post" multiple="multiple" class="pro-setting" disabled></select>';
		$button           = sprintf('<button class="button ilj-import" disabled style="cursor:not-allowed;">%s</button>', __('Import', 'internal-links'));
		$term_types_input = '<select name="ilj-import-intern-type" id="ilj-import-intern-type-term" multiple="multiple" class="pro-setting" disabled></select>';

		

		$output .= $post_types_input;
		$output .= '</div>';
		$output .= '<div class="clear"></div>';
		$output .= '</div>';

		$output .= sprintf('<p><strong>%s</strong></p>', __('Sources', 'internal-links'));

		$output .= $this->getKeywordImportSource(
			array(
				array(
					'title' => __('Post titles', 'internal-links'),
					'class' => 'title',
				),
				array(
					'title' => __('Post tags', 'internal-links'),
					'class' => 'tags',
				),
			),
			self::ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_POST,
			self::ILJ_MENUPAGE_TOOLS_IMPORT_INTERN_POST
		);

		$output .= $button;
		$output .= '<span class="spinner"></span>';
		$output .= '<div class="clear"></div>';
		$output .= '</form>';
		$output .= '</div>';

		$output .= '<div class="divide"></div>';

		$output .= '<div class="wrap">';
		$output .= sprintf('<h3>%s</h3>', __('Import for taxonomies from internal resources', 'internal-links'));
		$output .= '<form method="post" name="ilj-import-keywords-term" class="ilj-import-keywords" data-type-import="term">';
		$output .= '<div class="ilj-row">';
		$output .= '<div class="col-6">';
		$output .= __('Types to apply import', 'internal-links');
		$output .= '</div>';
		$output .= '<div class="col-6">';

		$output .= $term_types_input;
		$output .= '</div>';
		$output .= '<div class="clear"></div>';
		$output .= '</div>';

		$output .= sprintf('<p><strong>%s</strong></p>', __('Sources', 'internal-links'));

		$output .= $this->getKeywordImportSource(
			array(
				'title' => __('Term titles', 'internal-links'),
				'class' => 'title',
			),
			self::ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_TERM,
			self::ILJ_MENUPAGE_TOOLS_IMPORT_INTERN_TERM
		);

		$button = sprintf('<button class="button ilj-import" disabled style="cursor:not-allowed;">%s</button>', __('Import', 'internal-links'));

		
		$output .= $button;
		$output .= '<span class="spinner"></span>';
		$output .= '<div class="clear"></div>';
		$output .= '</form>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Returns the import source checkboxes for an import type
	 *
	 * @since 1.2.0
	 * @param array  $source        The source parameters
	 * @param string $filter        Name of the filter that collects available sources
	 * @param string $toggler_class Class of the toggler
	 *
	 * @return string
	 */
	protected function getKeywordImportSource(array $source, $filter, $toggler_class) {
		$output = '';

		$keyword_import_source = $source;
		if (!wp_is_numeric_array($source)) {
			$keyword_import_source = array($source);
		}
		/**
		 * Filters the available import source entries
		 *
		 * @since 1.2.0
		 *
		 * @param array $keyword_import_source Existing import source entries
		 */
		$keyword_import_source = apply_filters($filter, $keyword_import_source);

		foreach ($keyword_import_source as $source) {
			if (!isset($source['title']) || !isset($source['class'])) {
				continue;
			}

			$output  .= '<div class="ilj-row import-source">';
			$output  .= '<div class="col-6">';
			$output  .= $source['title'];
			$output  .= '</div>';
			$output  .= '<div class="col-6">';
			$disabled = 'disabled';
			
			$output .= Options::getToggleField($toggler_class . '-' . $source['class'], 0, $disabled);
			$output .= '</div>';
			$output .= '<div class="clear"></div>';
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Handles the export get requests to current screen
	 *
	 * @since  1.2.0
	 * @param  WP_Screen $current_screen Current WP_Screen object.
	 * @return void
	 */
	public function exportScreen($current_screen) {
		if ($current_screen->base != $this->page_hook) {
			return;
		}

		if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'ilj-tools')) {
			return;
		}

		$export_request = isset($_GET['ilj_export']) ? sanitize_text_field(wp_unslash($_GET['ilj_export'])) : null;

		if (!$export_request || !in_array($export_request, array('settings', 'keywords'))) {
			return;
		}

		$file_name = 'ilj_' . $export_request;

		

		header_remove();

		switch ($export_request) {
			case 'settings':
			header('Content-type: application/json; charset=utf-8');
			header(sprintf('Content-disposition: attachment; filename="%s.json"', $file_name));
			echo wp_json_encode(\ILJ\Core\Options::exportOptions(), JSON_UNESCAPED_SLASHES);
				die("");
				break;
			case 'keywords':
			$option_empty = isset($_GET['empty']) && (bool) $_GET['empty'] ? false : true;
			$option_additional_cols = isset($_GET['addcols']) && (bool) $_GET['addcols'] ? true : false;

			header('Content-type: application/csv; charset=utf-8');
			header(sprintf('Content-disposition: attachment; filename="%s.csv"', $file_name));

			Export::printCsvHeadline(false, $option_additional_cols);
			Export::printCsvPosts($option_empty, false, $option_additional_cols);

			
				die("");
				break;
		}
	}

	

	

	

	
}
