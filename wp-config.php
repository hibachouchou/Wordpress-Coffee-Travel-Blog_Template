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
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress_blog' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         ')x,+nitfi~ 9m2kuXHN;1rf{`~!=B|T)P9 &tg4n5g_nH__bzcT)dY_RGe4c5Xv|' );
define( 'SECURE_AUTH_KEY',  '&Ao,*]O0)gp,m*SMNMH;qQ3i[A9k]+w^L%a,SGRT=!rIBX|jB>~D$WnE7grcDX:L' );
define( 'LOGGED_IN_KEY',    'Sc^LSgYbo_0zo9IR~/;*aWk.TE>cs7[EC?8XiukF+;1o*DT~ZbR+qQLM]~>LYVZZ' );
define( 'NONCE_KEY',        'oBk51wOzbM~ET@cJ4nqCU-=?p^)3qoWcn@9kDoTD_Y8PZ.:/@O-G[^M3_E0fqw}=' );
define( 'AUTH_SALT',        '@X.y*m.p12}^PW4g7PttL^1T1_kf@+G1(t_wlnvpW9r@qwyvDc_E0>YwbN8?}Xz4' );
define( 'SECURE_AUTH_SALT', 'QOn-VKjUG;eG!uQQrNEE.4n<ZePx9|rcHrm.w> yt=;sUBI(zPYs$SU;@-ZQGuns' );
define( 'LOGGED_IN_SALT',   ']7M>#L2PHEc(i:gNB2jy4e7%0E:=4HWQh,AN-u@xJB;+m;*TA}m=KU>F;srU;l5C' );
define( 'NONCE_SALT',       'TkZKE%pm$K[ bcKFi8cM-IOt4F.rXNb%W#aa%*lpkrgKkHbp^1:KcKSG9JwmGW:V' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'blog_wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
