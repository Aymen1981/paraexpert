<?php
/**
 * Plugin Name: ParaExpert - Coordonnées de facturation dans les emails
 * Description: Affiche dans les emails WooCommerce les mêmes informations que le formulaire de commande : nom, prénom, email, téléphone 1 et 2, adresse, ville, district, quartier et note du client.
 * Version: 1.0.0
 * Author: ParaExpert
 */

defined( 'ABSPATH' ) || exit;

/**
 * Correspondance libellé => clé de meta de la commande.
 *
 * ADAPTEZ les clés "phone_2", "district" et "quartier" à celles de votre
 * formulaire (voir README : WooCommerce > Commandes > ouvrir une commande >
 * "Champs personnalisés", ou le réglage de votre plugin de champs checkout).
 * Les clés avec "_" au début sont les meta internes (ex. _billing_phone_2).
 * Plusieurs clés possibles par champ : la première non vide est utilisée.
 */
function paraexpert_email_fields_map() {
	return apply_filters( 'paraexpert_email_fields_map', array(
		'Nom'          => array( '_billing_last_name' ),
		'Prénom'       => array( '_billing_first_name' ),
		'Email'        => array( '_billing_email' ),
		'Téléphone 1'  => array( '_billing_phone' ),
		'Téléphone 2'  => array( '_billing_phone_2', '_billing_phone2', 'billing_phone_2', '_billing_telephone_2' ),
		'Adresse'      => array( '_billing_address_1' ),
		'Ville'        => array( '_billing_city' ),
		'District'     => array( '_billing_state', '_billing_district', 'billing_district' ),
		'Quartier'     => array( '_billing_quartier', 'billing_quartier', '_billing_address_2', '_billing_neighborhood' ),
	) );
}

function paraexpert_get_order_value( $order, array $keys ) {
	foreach ( $keys as $key ) {
		$value = $order->get_meta( $key, true );
		if ( '' === $value || null === $value ) {
			// Les champs standards ont aussi un getter (ex. get_billing_city).
			$getter = 'get' . $key;
			if ( 0 === strpos( $key, '_billing_' ) && method_exists( $order, $getter ) ) {
				$value = $order->$getter();
			}
		}
		if ( '' !== $value && null !== $value ) {
			// Convertit un code d'état WooCommerce (ex. TN-11) en nom lisible.
			if ( '_billing_state' === $key ) {
				$states = WC()->countries->get_states( $order->get_billing_country() );
				if ( is_array( $states ) && isset( $states[ $value ] ) ) {
					$value = $states[ $value ];
				}
			}
			return is_scalar( $value ) ? (string) $value : '';
		}
	}
	return '';
}

/**
 * Remplace la liste des champs "client" des emails (email + téléphone par défaut)
 * par la liste complète.
 */
add_filter( 'woocommerce_email_customer_details_fields', function ( $fields, $sent_to_admin, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $fields;
	}

	$fields = array();
	foreach ( paraexpert_email_fields_map() as $label => $keys ) {
		$value = paraexpert_get_order_value( $order, $keys );
		if ( '' !== $value ) {
			$fields[ sanitize_title( $label ) ] = array(
				'label' => $label,
				'value' => wptexturize( $value ),
			);
		}
	}

	$note = $order->get_customer_note();
	if ( $note ) {
		$fields['note-client'] = array(
			'label' => 'Note du client',
			'value' => wptexturize( $note ),
		);
	}

	return $fields;
}, 20, 3 );

/**
 * Évite les doublons : le bloc "Adresse de facturation" standard est masqué
 * dans les emails car toutes les infos sont maintenant dans la liste ci-dessus.
 * Pour le conserver, supprimez ce filtre.
 */
add_filter( 'woocommerce_order_get_formatted_billing_address', function ( $address, $raw, $order ) {
	if ( did_action( 'woocommerce_email_customer_details' ) || doing_action( 'woocommerce_email_customer_details' ) ) {
		return '';
	}
	return $address;
}, 20, 3 );

/**
 * La note du client figure déjà dans la liste : on la retire du tableau
 * de récapitulatif de commande pour ne pas l'afficher deux fois.
 */
add_filter( 'woocommerce_get_order_item_totals', function ( $totals ) {
	if ( doing_action( 'woocommerce_email_order_details' ) ) {
		unset( $totals['customer_note'] );
	}
	return $totals;
}, 20 );
