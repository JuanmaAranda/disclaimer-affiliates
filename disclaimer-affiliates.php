<?php
/*
Plugin Name: Disclaimer Affiliates
Description: Este plugin añade un texto de forma automática al final de todas las entradas de WordPress enlazando al descargo de responsabilidad de afiliados.
Version: 1.0
Author: Juanma Aranda
Author URI: https://wpnovatos.com/
License: GPLv2 or later
Text Domain: disclaimer
*/


// Añadimos un panel de control en el menú de ajustes de WordPress
function disclaimer_add_options_page() {
	add_options_page(
		'Disclaimer Affiliates',
		'Disclaimer Affiliates',
		'manage_options',
		'disclaimer',
		'disclaimer_options_page'
	);
}
add_action( 'admin_menu', 'disclaimer_add_options_page' );

// Creamos el formulario del panel de control del plugin
function disclaimer_options_page() {
	?>
	<div class="wrap">
	<h1>Descargo de responsabilidad de afiliados</h1>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'disclaimer_settings' );
		do_settings_sections( 'disclaimer' );
		submit_button();
		?>
	</form>
</div>
<?php
}


// Añadimos una sección de instrucciones en el panel de control del plugin
function disclaimer_add_settings_section() {
add_settings_section(
'disclaimer_section',
'Instrucciones',
'disclaimer_section_callback',
'disclaimer'
);
}
add_action( 'admin_init', 'disclaimer_add_settings_section' );

// Mostramos el texto de instrucciones
function disclaimer_section_callback() {
echo '<p>Por favor, selecciona la página que contiene el descargo de responsabilidad de afiliados de tu sitio y el tamaño del texto con el que quieres que se muestre el aviso y el enlace en tus entradas.</p> <p>Recuerda que deberás activar su impresión de forma individual en cada entrada.</p>';
}




// Registramos las opciones del plugin y añadimos una función de validación
function disclaimer_register_settings() {
register_setting( 'disclaimer_settings', 'disclaimer_settings', 'disclaimer_validate_settings' );
add_settings_section( 'disclaimer_section_general', '', '', 'disclaimer' );
add_settings_field( 'disclaimer_field_page', 'Página de descargo de responsabilidad', 'disclaimer_field_page_render', 'disclaimer', 'disclaimer_section_general' );
add_settings_field( 'disclaimer_field_font_size', 'Tamaño de la fuente (en píxeles)', 'disclaimer_field_font_size_render', 'disclaimer', 'disclaimer_section_general' );
}
add_action( 'admin_init', 'disclaimer_register_settings' );

// Mostramos el campo selector para elegir la página de descargo de responsabilidad
function disclaimer_field_page_render() {
$options = get_option( 'disclaimer_settings' );
$selected_page = ( isset( $options['page'] ) ) ? $options['page'] : '';
wp_dropdown_pages( array(
'name' => 'disclaimer_settings[page]',
'selected' => $selected_page,
'show_option_none' => '--- Selecciona una página ---',
'value' => 'ID',
) );
}

// Mostramos el campo para seleccionar el tamaño de la fuente
function disclaimer_field_font_size_render() {
$options = get_option( 'disclaimer_settings' );
$font_size = ( isset( $options['font_size'] ) ) ? $options['font_size'] : '';
echo '<input type="number" name="disclaimer_settings[font_size]" value="' . esc_attr( $font_size ) . '" min="8" max="50" step="1" />';
}

// Función de validación para asegurarnos de que se han rellenado correctamente todos los campos
function disclaimer_validate_settings( $input ) {
$output = array();
if ( isset( $input['page'] ) && ! empty( $input['page'] ) ) {
$output['page'] = intval( $input['page'] );
}
if ( isset( $input['font_size'] ) && ! empty( $input['font_size'] ) ) {
$output['font_size'] = intval( $input['font_size'] );
}
return $output;
}

// Añadimos un selector, tipo slider, dentro de las preferencias generales de cada entrada y de los "coupon"
function disclaimer_add_meta_box() {
add_meta_box(
'disclaimer_meta_box',
'Disclaimer Affiliates',
'disclaimer_meta_box_render',
array( 'post', 'coupon' ),
'side',
'default'
);
}
add_action( 'add_meta_boxes', 'disclaimer_add_meta_box' );

// Mostramos el texto y enlazamos la expresión "Descargo de responsabilidad de este sitio" a la página de descargo de responsabilidad seleccionada en el panel de control del plugin
function disclaimer_meta_box_render( $post ) {
$options = get_option( 'disclaimer_settings' );
$selected_page = ( isset( $options['page'] ) ) ? $options['page'] : '';
if ( ! empty( $selected_page ) ) {
$disclaimer_url = get_permalink( $selected_page );
echo '<p>Muestra un texto estándar enlazando al descargo de responsabilidad de tu sitio al final de la entrada.</p>';
} else {
echo '<p style="color: red;">Para incluir el Disclaimer, debes configurar antes todos los campos desde <strong>Ajustes > Disclaimer</strong>.</p>';
}
wp_nonce_field( 'disclaimer_nonce', 'disclaimer_nonce' );
echo '<label for="disclaimer_checkbox"><input type="checkbox" id="disclaimer_checkbox" name="disclaimer_checkbox" value="1"' . checked( 1, get_post_meta( $post->ID, '_disclaimer_checkbox', true ), false ) . ' /> Añadir Disclaimer</label>';
}

// Guardamos el estado del selector en las preferencias generales de cada entrada y de los "coupon"
function disclaimer_save_meta_box_data( $post_id ) {
if ( ! isset( $_POST['disclaimer_nonce'] ) || ! wp_verify_nonce( $_POST['disclaimer_nonce'], 'disclaimer_nonce' ) ) {
return;
}
if ( ! current_user_can( 'edit_post', $post_id ) ) {
return;
}
if ( isset( $_POST['disclaimer_checkbox'] ) ) {
update_post_meta( $post_id, '_disclaimer_checkbox', 1 );
} else {
update_post_meta( $post_id, '_disclaimer_checkbox', 0 );
}
}
add_action( 'save_post', 'disclaimer_save_meta_box_data' );



// Añadimos el texto de Disclaimer al final de todas las entradas y en las entradas tipo "coupon" cuando se ha activado el selector en las preferencias generales de cada entrada
function disclaimer_the_content( $content ) {
if ( is_singular( array( 'post', 'coupon' ) ) ) {
$options = get_option( 'disclaimer_settings' );
$selected_page = ( isset( $options['page'] ) ) ? $options['page'] : '';
$font_size = ( isset( $options['font_size'] ) ) ? $options['font_size'] : '14';
if ( get_post_meta( get_the_ID(), '_disclaimer_checkbox', true ) && ! empty( $selected_page ) ) {
$disclaimer_url = get_permalink( $selected_page );
$disclaimer_text = '<div style="border: 1px solid #000; padding: 10px; font-size: ' . esc_attr( $font_size ) . 'px;"><strong>Disclaimer</strong>: Esta publicación puede contener uno o varios enlaces de afiliado. Para más información, consulta el <a href="' . esc_url( $disclaimer_url ) . '">descargo de responsabilidad de este sitio</a>.</div>';
$content .= $disclaimer_text;
}
}
return $content;
}
add_filter( 'the_content', 'disclaimer_the_content' );

// Activamos el plugin
function disclaimer_activate() {
disclaimer_add_options_page();
disclaimer_register_settings();
flush_rewrite_rules();
}
register_activation_hook( FILE, 'disclaimer_activate' );

// Desactivamos el plugin
function disclaimer_deactivate() {
flush_rewrite_rules();
}
register_deactivation_hook( FILE, 'disclaimer_deactivate' );

// Desinstalamos el plugin
function disclaimer_uninstall() {
delete_option( 'disclaimer_settings' );
}
register_uninstall_hook( FILE, 'disclaimer_uninstall' );

?>