<?php
/**
 * This file is part of the Memento Extension to MediaWiki
 * https://www.mediawiki.org/wiki/Extension:Memento
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * Main Memento class, used by hooks.
 *
 * This class handles the entry point from Mediawiki and performs
 * the mediation over the real work.  The goal is to separate
 * the Mediawiki setup code from the Memento code as much as possible
 * for clarity, testing, maintainability, etc.
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

class Memento {

	/**
	 * The BeforeParserFetchTemplateAndtitle hook, used here to change any
	 * Template pages loaded so that their revision is closer in date/time to
	 * that of the rest of the page.
	 *
	 * @param Parser $parser Parser object for this page
	 * @param Title $title Title object for this page
	 * @param boolean $skip boolean flag allowing the caller to skip the rest of statelessFetchTemplate
	 * @param integer $id revision id of this page
	 *
	 * @return boolean indicating success to the caller
	 */
	public static function onBeforeParserFetchTemplateAndtitle(
		$parser, $title, &$skip, &$id ) {

		// This creates the error Wikimedia\Rdbms\DBUnexpectedError from line 3843 of /var/www/html/includes/libs/rdbms/database/Database.php: Wikimedia\Rdbms\Database::begin: Implicit transaction already active (from Wikimedia\Rdbms\Database::query (User::loadFromDatabase)).

		$db = wfGetDB( DB_REPLICA );
		MementoResource::fixTemplate( $title, $parser, $id, $db );

		return true;
	}

	/**
	 * The ArticleViewHeader hook, used to alter the headers before the rest
	 * of the data is loaded.
	 *
	 * Note: this is not called when the Edit, Diff or History pages are loaded.
	 *
	 * @param Article &$article pointer to the Article Object from the hook
	 * @param bool &$outputDone pointer to variable that indicates that
	 *                         the output should be terminated
	 * @param bool &$pcache pointer to variable that indicates whether the parser
	 * 			cache should try retrieving the cached results
	 *
	 * @return bool indicating success to the caller
	 */
	public static function onArticleViewHeader(
		&$article, &$outputDone, &$pcache
		) {
		// avoid processing Mementos for nonexistent pages
		// if we're an article, do memento processing, otherwise don't worry
		// if we're a diff page, Memento doesn't make sense
		if ( $article->getTitle()->isKnown() ) {

			$revision = $article->getRevisionFetched();

			// avoid processing Mementos for bad revisions,
			// let MediaWiki handle that case instead
			if ( is_object( $revision ) ) {

				$db = wfGetDB( DB_REPLICA );
				$oldID = $article->getOldID();
				$request = $article->getContext()->getRequest();

				$mementoResource = MementoResource::mementoPageResourceFactory( $db, $article, $oldID );

				$mementoResource->alterHeaders();
			}
		}

		return true;
	}

}
