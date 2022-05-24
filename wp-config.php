<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en « wp-config.php » et remplir les
 * valeurs.
 *
 * Ce fichier contient les réglages de configuration suivants :
 *
 * Réglages MySQL
 * Préfixe de table
 * Clés secrètes
 * Langue utilisée
 * ABSPATH
 *
 * @link https://fr.wordpress.org/support/article/editing-wp-config-php/.
 *
 * @package WordPress
 */

define('FS_METHOD', 'direct');

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'silogik' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'root' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', '' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Type de collation de la base de données.
 * N’y touchez que si vous savez ce que vous faites.
 */
define( 'DB_COLLATE', '' );


// define('WP_HOME','192.168.212.66:85');
// define('WP_SITEURL','192.168.212.66:85');


/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clés secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'sM%kV*cKY#{9ej7r7?7Vak9d4*/vczn31 *qgd xRjCFc#d(b@_XUv*@+UUFkvNS' );
define( 'SECURE_AUTH_KEY',  'VR7 yU,y0R=t>= ZIH$#YZ!:&K[R:1LWU;grO!yx?s+M^<n@&#!Si`q,N$R3WjM)' );
define( 'LOGGED_IN_KEY',    'P2{E/]PEX<:l[PgUDjT4}EeHr[%*DztiCqc<ruK{==p83}t`cT2Gj{9w&!]Kk5ux' );
define( 'NONCE_KEY',        'DYL{0on.B+J&=44I>g4a!#1iCH^b|u1$8!Kaw^2T:xi8x4=f` Y.ut%dq _yE[vP' );
define( 'AUTH_SALT',        '#S.SLTL0JbG6UYYS=eD&1)BQ4/eD=6P[]$-F6G[+5>A2p`2U~[x%}Fdg,NHP; jl' );
define( 'SECURE_AUTH_SALT', '(>!@~G5TptZJiExy053.<u!P9,| (wlx,j6b=^oQ8WeU}^K%jm0b?QpI(wYX;Dm6' );
define( 'LOGGED_IN_SALT',   '(ep+CRpZlxa~H]7VpyLZEO1!3#.Zx~*t+4RPLo^kcu}?]H#3;Y>4b8@f%_aj]XL6' );
define( 'NONCE_SALT',       '@sOXuf3D/ktOj;f?{W*lgnLk^fxm)^yuV&/)|4LJ:$ZBlRL$owC.n^7KhD^fwp-=' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortement recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://fr.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( ! defined( 'ABSPATH' ) )
  define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once( ABSPATH . 'wp-settings.php' );
