<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'aQ06Ix|rhmjm}_S(uc%=),Vf1RAoVJ[]Xi<C@QvP+C8G_|&})W-FJSBaAqlNsQ?z' );
define( 'SECURE_AUTH_KEY',   'pgJ<Hr67SjY;(wF<1:FYKWtzS}UE@~my9lFcA[w<I8T1+*E&lT=6jZD*1@R:-,v$' );
define( 'LOGGED_IN_KEY',     '=Z2Z4PW!H.seyq*=dfz/_So1Zu|2Qdtzm>:APp&+&j^1>zhp$pQ!.3#lcid_eiMF' );
define( 'NONCE_KEY',         'A~s31^nPP:s!;3x7`4K^|]^,Ady}0V29EA!J=|E9!}jera[%J*U!f?X</V#s=o{~' );
define( 'AUTH_SALT',         'O>??qgs0r:8P8|;a zyIbAb@6*2UtVEaRsUx5c}/}):uC}8;^.e_;0,ZwAtsk}`F' );
define( 'SECURE_AUTH_SALT',  '$?G9HvU#1jqZT5LSEG$KrgRJ#jc1;VP `a[P5TG~GwrfF>9#Z2zm3X1afsFAO4;,' );
define( 'LOGGED_IN_SALT',    '{vz2twV(%i-g7`q`~EjVdq{3!CiQ;W6(aCal.~;u=g=-y|+2nsH-^hkb4yud!:}$' );
define( 'NONCE_SALT',        'X_YO$.M/q^/^]9(&1[)PkO|5#&/SP2kJ;rMGen+ZH)43_%2u?Ukuvvscx2qz){yF' );
define( 'WP_CACHE_KEY_SALT', '.R>eGg|[ss^4XhD8.nJyfH qw=dBIb^L xISLl.MTR;ANXzz0Z#z%WR2E{>B@u1`' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
