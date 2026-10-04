<?php
/**
 * Page d'un établissement, d'un département, d'une filière ou d'une UE :
 * fil d'Ariane jusqu'à l'établissement, puis le niveau suivant.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$id       = get_the_ID();
$niveaux  = sueb_niveaux();
$prof     = min( 3, sueb_profondeur( $id ) );
$racine   = sueb_racine( $id );
$sigle    = (string) sueb_meta( $racine, 'sigle' );
$fixe     = sueb_etablissements()[ strtoupper( $sigle ) ] ?? array( 'couleur' => '' );
$couleur  = sueb_meta( $racine, 'couleur' ) ?: $fixe['couleur'];
$enfants  = get_children( array( 'post_parent' => $id, 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
$syllabus = (int) sueb_meta( $id, 'syllabus' );
?>
<header class="page-tete" style="--etab:<?php echo esc_attr( $couleur ); ?>">
	<div class="conteneur">
		<ol class="fil-ariane">
			<li><a href="<?php echo esc_url( home_url( '/#etablissements' ) ); ?>">Établissements</a></li>
			<?php foreach ( array_reverse( get_post_ancestors( $id ) ) as $a ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( sueb_meta( $a, 'sigle' ) ?: get_the_title( $a ) ); ?></a></li>
			<?php endforeach; ?>
		</ol>
		<p class="surtitre surtitre--clair"><?php echo esc_html( $niveaux[ $prof ]['un'] ); ?><?php echo 3 === $prof && sueb_meta( $id, 'code' ) ? ' · ' . esc_html( sueb_meta( $id, 'code' ) ) : ''; ?></p>
		<h1><?php the_title(); ?></h1>
	</div>
</header>
<div class="conteneur page-corps" style="--etab:<?php echo esc_attr( $couleur ); ?>">
	<div class="prose"><?php the_content(); ?></div>

	<?php if ( 3 === $prof ) : ?>
		<div class="fiche-ue" style="border-radius:var(--r-carte);max-width:720px">
			<dl>
				<div><dt>Crédits</dt><dd><?php echo esc_html( sueb_meta( $id, 'credits' ) ?: '—' ); ?></dd></div>
				<div><dt>Semestre</dt><dd><?php echo esc_html( sueb_meta( $id, 'semestre' ) ?: '—' ); ?></dd></div>
				<div><dt>Volume</dt><dd style="font-size:1rem"><?php echo esc_html( sueb_meta( $id, 'volume' ) ?: '—' ); ?></dd></div>
			</dl>
			<?php if ( $syllabus ) : ?>
				<a class="btn btn--plein" href="<?php echo esc_url( wp_get_attachment_url( $syllabus ) ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 18 ); ?>Télécharger le syllabus</a>
			<?php else : ?>
				<p class="vide">Syllabus bientôt disponible.</p>
			<?php endif; ?>
		</div>
	<?php elseif ( $enfants ) : ?>
		<h2 class="section__titre-2" style="margin-top:2rem"><?php echo esc_html( $niveaux[ $prof ]['enfants'] ); ?></h2>
		<ul class="grille-enfants">
			<?php foreach ( $enfants as $e ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $e ) ); ?>">
					<strong><?php echo esc_html( $e->post_title ); ?></strong>
					<small><?php echo esc_html( 2 === $prof ? trim( sueb_meta( $e->ID, 'code' ) . ( sueb_meta( $e->ID, 'credits' ) ? ' · ' . sueb_meta( $e->ID, 'credits' ) . ' crédits' : '' ), ' ·' ) : (string) sueb_meta( $e->ID, 'responsable' ) ); ?></small>
				</a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="vide">Contenu en cours de publication.</p>
	<?php endif; ?>
</div>
<?php
get_footer();
