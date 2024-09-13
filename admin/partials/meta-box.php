<label for="ims_movie_id"><?php echo esc_html($labels['movie_id'] ?? ''); ?></label>
<input type="text" id="ims_movie_id" name="ims_movie_id" value="<?php echo esc_attr($movie_data['imdbID'] ?? ''); ?>" style="width: 100%;" />

<h4><?php esc_html_e('Movie Details', 'imdb-scraper'); ?></h4>
<?php
$fields = [
    'title', 'year', 'rated', 'released', 'runtime', 'genre', 'director',
    'writer', 'actors', 'plot', 'country', 'awards', 'imdbRating', 'imdbVotes'
];

foreach ($fields as $field) {
    ?>
    <label for="ims_<?php echo esc_attr($field); ?>"><?php echo esc_html($labels[$field]); ?></label>
    <?php if ($field === 'plot'): ?>
        <textarea id="ims_<?php echo esc_attr($field); ?>" name="ims_<?php echo esc_attr($field); ?>" rows="4" style="width: 100%;"><?php echo esc_textarea($movie_data[$field] ?? ''); ?></textarea>
    <?php else: ?>
        <input type="text" id="ims_<?php echo esc_attr($field); ?>" name="ims_<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($movie_data[$field] ?? ''); ?>" style="width: 100%;" />
    <?php endif; ?>
    <?php
}
?>
