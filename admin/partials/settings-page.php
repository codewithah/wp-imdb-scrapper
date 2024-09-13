<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('ims-settings-group');
        do_settings_sections('ims-settings-group');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('OMDB API Key', 'imdb-scraper'); ?></th>
                <td>
                    <input type="text" name="ims_api_key" value="<?php echo esc_attr(get_option('ims_api_key')); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Default Display Options', 'imdb-scraper'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php esc_html_e('Default Display Options', 'imdb-scraper'); ?></span></legend>
                        <?php
                        $options = get_option('ims_display_options', []);
                        $fields = [
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
                        foreach ($fields as $key => $label) {
                            ?>
                            <label>
                                <input type="checkbox" name="ims_display_options[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $options)); ?> />
                                <?php echo esc_html($label); ?>
                            </label><br />
                            <?php
                        }
                        ?>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
