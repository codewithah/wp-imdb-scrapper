<?php
class IMDb_Scraper {
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // TODO: Load any necessary dependencies here
    }

    private function set_locale() {
        add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'imdb-scraper',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    private function define_admin_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_menu', [$this, 'create_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_custom_meta_box']);
        add_action('save_post', [$this, 'save_custom_meta']);
    }

    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_scripts']);
        add_shortcode('ims_movie_info', [$this, 'movie_info_shortcode']);
    }

    public function enqueue_admin_styles() {
        wp_enqueue_style('ims-admin-style', IMDB_SCRAPER_PLUGIN_URL . 'admin/css/admin.css', [], IMDB_SCRAPER_VERSION, 'all');
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('ims-admin-script', IMDB_SCRAPER_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], IMDB_SCRAPER_VERSION, false);
    }

    public function enqueue_public_styles() {
        wp_enqueue_style('ims-public-style', IMDB_SCRAPER_PLUGIN_URL . 'public/css/public.css', [], IMDB_SCRAPER_VERSION, 'all');
    }

    public function enqueue_public_scripts() {
        wp_enqueue_script('ims-public-script', IMDB_SCRAPER_PLUGIN_URL . 'public/js/public.js', ['jquery'], IMDB_SCRAPER_VERSION, false);
    }

    public function create_admin_menu() {
        add_menu_page('IMDb Scraper', 'IMDb Scraper', 'manage_options', 'ims-settings', [$this, 'settings_page'], 'dashicons-video-alt3', 30);
    }

    public function settings_page() {
        require_once IMDB_SCRAPER_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    public function register_settings() {
        register_setting('ims-settings-group', 'ims_api_key');
        register_setting('ims-settings-group', 'ims_display_options');
    }

    public function add_custom_meta_box() {
        add_meta_box(
            'ims_movie_id_meta_box',
            'IMDb Movie ID',
            [$this, 'render_custom_meta_box'],
            ['post', 'page'],
            'advanced'
        );
    }

    public function render_custom_meta_box($post) {
        // Retrieve existing movie data
        $movie_data_json = get_post_meta($post->ID, '_ims_movie_data', true);
        $movie_data = json_decode($movie_data_json, true);

        // If no movie data exists, initialize an empty array
        if (!$movie_data) {
            $movie_data = [];
        }

        // Get the meta box labels
        $labels = $this->get_meta_box_labels();

        // Include the meta box partial
        require_once IMDB_SCRAPER_PLUGIN_DIR . 'admin/partials/meta-box.php';
    }

    public function save_custom_meta($post_id) {
        if (isset($_POST['ims_movie_id'])) {
            $movie_id = sanitize_text_field($_POST['ims_movie_id']);
            update_post_meta($post_id, '_ims_movie_id', $movie_id);

            // Prepare movie data for saving
            $movie_data = [
                'imdbID' => $movie_id,
                'title' => sanitize_text_field($_POST['ims_title']),
                'year' => sanitize_text_field($_POST['ims_year']),
                'rated' => sanitize_text_field($_POST['ims_rated']),
                'released' => sanitize_text_field($_POST['ims_released']),
                'runtime' => sanitize_text_field($_POST['ims_runtime']),
                'genre' => sanitize_text_field($_POST['ims_genre']),
                'director' => sanitize_text_field($_POST['ims_director']),
                'writer' => sanitize_text_field($_POST['ims_writer']),
                'actors' => sanitize_text_field($_POST['ims_actors']),
                'plot' => sanitize_textarea_field($_POST['ims_plot']),
                'country' => sanitize_text_field($_POST['ims_country']),
                'awards' => sanitize_text_field($_POST['ims_awards']),
                'imdbRating' => sanitize_text_field($_POST['ims_imdbRating']),
                'imdbVotes' => sanitize_text_field($_POST['ims_imdbVotes']),
            ];

            // Fetch movie data from OMDB API
            $movie_data = array_merge($movie_data, $this->fetch_movie_data($movie_id));

            // Store the movie data as a JSON string
            update_post_meta($post_id, '_ims_movie_data', json_encode($movie_data));

            // Insert movie data into the database
            $this->insert_movie_data($movie_data, $post_id);
        }
    }

    private function fetch_movie_data($id) {
        $api_key = get_option('ims_api_key');
        $url = "http://www.omdbapi.com/?i={$id}&apikey={$api_key}";

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return ['error' => esc_html__('Unable to retrieve data.', 'imdb-scraper')];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['Error'])) {
            return ['error' => esc_html($data['Error'])];
        }

        // Download the poster image
        $poster_url = $data['Poster'] ?? 'N/A';
        $local_poster_path = $this->download_image($poster_url);

        return [
            'title' => $data['Title'] ?? 'N/A',
            'year' => $data['Year'] ?? 'N/A',
            'rated' => $data['Rated'] ?? 'N/A',
            'released' => $data['Released'] ?? 'N/A',
            'runtime' => $data['Runtime'] ?? 'N/A',
            'genre' => $data['Genre'] ?? 'N/A',
            'director' => $data['Director'] ?? 'N/A',
            'writer' => $data['Writer'] ?? 'N/A',
            'actors' => $data['Actors'] ?? 'N/A',
            'plot' => $data['Plot'] ?? 'N/A',
            'language' => $data['Language'] ?? 'N/A',
            'country' => $data['Country'] ?? 'N/A',
            'awards' => $data['Awards'] ?? 'N/A',
            'poster' => $local_poster_path,
            'ratings' => $data['Ratings'] ?? [],
            'metascore' => $data['Metascore'] ?? 'N/A',
            'imdbRating' => $data['imdbRating'] ?? 'N/A',
            'imdbVotes' => $data['imdbVotes'] ?? 'N/A',
            'imdbID' => $data['imdbID'] ?? 'N/A',
            'type' => $data['Type'] ?? 'N/A',
            'dvd' => $data['DVD'] ?? 'N/A',
            'boxOffice' => $data['BoxOffice'] ?? 'N/A',
            'production' => $data['Production'] ?? 'N/A',
            'website' => $data['Website'] ?? 'N/A',
            'response' => $data['Response'] ?? 'False',
        ];
    }

    private function download_image($url) {
        if (empty($url) || $url === 'N/A') {
            return null;
        }

        // Get the file name from the URL
        $file_name = basename($url);
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $file_name;

        // Download the image
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return null;
        }

        // Save the image to the uploads directory
        file_put_contents($file_path, wp_remote_retrieve_body($response));

        // Return the URL of the saved image
        return $upload_dir['url'] . '/' . $file_name;
    }

    private function insert_movie_data($movie_data, $post_id) {
        global $wpdb;

        // Prepare the data for insertion
        $table_name = $wpdb->prefix . 'movies';

        // Check if the movie already exists
        $existing_movie = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE imdbID = %s", $movie_data['imdbID']));

        if (!$existing_movie) {
            // Insert the movie data into the database
            $wpdb->insert($table_name, [
                'title' => $movie_data['title'],
                'year' => $movie_data['year'],
                'rated' => $movie_data['rated'],
                'released' => $movie_data['released'],
                'runtime' => $movie_data['runtime'],
                'genre' => $movie_data['genre'],
                'director' => $movie_data['director'],
                'writer' => $movie_data['writer'],
                'actors' => $movie_data['actors'],
                'plot' => $movie_data['plot'],
                'language' => $movie_data['language'],
                'country' => $movie_data['country'],
                'awards' => $movie_data['awards'],
                'poster' => $movie_data['poster'],
                'metascore' => $movie_data['metascore'],
                'imdbRating' => $movie_data['imdbRating'],
                'imdbVotes' => $movie_data['imdbVotes'],
                'imdbID' => $movie_data['imdbID'],
                'type' => $movie_data['type'],
                'dvd' => $movie_data['dvd'],
                'boxOffice' => $movie_data['boxOffice'],
                'production' => $movie_data['production'],
                'website' => $movie_data['website'],
                'response' => $movie_data['response'],
            ]);

            // Set the poster as the featured image
            if (!empty($movie_data['poster'])) {
                $this->set_thumbnail($post_id, $movie_data['poster']);
            }
        }
    }

    private function set_thumbnail($post_id, $image_url) {
        // Include the WordPress file system functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Download the image to the uploads directory
        $image_id = media_sideload_image($image_url, $post_id, null, 'id');

        // Set the downloaded image as the featured image
        if (!is_wp_error($image_id)) {
            set_post_thumbnail($post_id, $image_id);
        }
    }

    public function movie_info_shortcode($atts) {
        global $post;
        $movie_data_json = get_post_meta($post->ID, '_ims_movie_data', true);
        $movie_data = json_decode($movie_data_json, true);

        if (empty($movie_data)) {
            return '<p>' . esc_html__('No movie data found for this post.', 'imdb-scraper') . '</p>';
        }

        return $this->render_movie_info($movie_data);
    }

    private function render_movie_info($movie_data) {
        ob_start();
        $options = get_option('ims_display_options', []);
        $labels = $this->get_labels();

        ?>
        <div class="movie-info">
            <h2><?php echo esc_html($movie_data['title']); ?></h2>
            <?php foreach ($options as $option): ?>
                <?php if (array_key_exists($option, $labels)): ?>
                    <p><strong><?php echo esc_html($labels[$option]); ?>:</strong> <?php echo esc_html($movie_data[$option]); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!empty($movie_data['poster'])): ?>
                <img src="<?php echo esc_url($movie_data['poster']); ?>" alt="<?php echo esc_attr($movie_data['title']); ?>" />
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_labels() {
        return [
            'title' => __('Title', 'imdb-scraper'),
            'year' => __('Year', 'imdb-scraper'),
            'rated' => __('Rated', 'imdb-scraper'),
            'released' => __('Released', 'imdb-scraper'),
            'runtime' => __('Runtime', 'imdb-scraper'),
            'genre' => __('Genre', 'imdb-scraper'),
            'director' => __('Director', 'imdb-scraper'),
            'writer' => __('Writer', 'imdb-scraper'),
            'actors' => __('Actors', 'imdb-scraper'),
            'plot' => __('Plot', 'imdb-scraper'),
            'language' => __('Language', 'imdb-scraper'),
            'country' => __('Country', 'imdb-scraper'),
            'awards' => __('Awards', 'imdb-scraper'),
            'imdbRating' => __('IMDB Rating', 'imdb-scraper'),
            'imdbVotes' => __('IMDB Votes', 'imdb-scraper'),
        ];
    }

    private function get_meta_box_labels() {
        return [
            'movie_id' => __('IMDb Movie ID', 'imdb-scraper'),
            'title' => __('Title', 'imdb-scraper'),
            'year' => __('Year', 'imdb-scraper'),
            'rated' => __('Rated', 'imdb-scraper'),
            'released' => __('Released', 'imdb-scraper'),
            'runtime' => __('Runtime', 'imdb-scraper'),
            'genre' => __('Genre', 'imdb-scraper'),
            'director' => __('Director', 'imdb-scraper'),
            'writer' => __('Writer', 'imdb-scraper'),
            'actors' => __('Actors', 'imdb-scraper'),
            'plot' => __('Plot', 'imdb-scraper'),
            'language' => __('Language', 'imdb-scraper'),
            'country' => __('Country', 'imdb-scraper'),
            'awards' => __('Awards', 'imdb-scraper'),
            'imdbRating' => __('IMDB Rating', 'imdb-scraper'),
            'imdbVotes' => __('IMDB Votes', 'imdb-scraper'),
        ];
    }

    public function run() {
        // This method can be used to initialize the plugin
    }
}