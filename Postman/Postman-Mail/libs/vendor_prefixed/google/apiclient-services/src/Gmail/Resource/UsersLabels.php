<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace PostSMTP\Vendor\Google\Service\Gmail\Resource;

use PostSMTP\Vendor\Google\Service\Gmail\Label;
use PostSMTP\Vendor\Google\Service\Gmail\ListLabelsResponse;
/**
 * The "labels" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gmailService = new Google\Service\Gmail(...);
 *   $labels = $gmailService->users_labels;
 *  </code>
 */
class UsersLabels extends \PostSMTP\Vendor\Google\Service\Resource {

	/**
	 * Creates a new label. (labels.create)
	 *
	 * @param string $userId The user's email address. The special value `me` can be
	 * used to indicate the authenticated user.
	 * @param Label  $postBody
	 * @param array  $optParams Optional parameters.
	 * @return Label
	 */
	public function create( $userId, \PostSMTP\Vendor\Google\Service\Gmail\Label $postBody, $optParams = array() ) {
		$params = array(
			'userId'   => $userId,
			'postBody' => $postBody,
		);
		$params = \array_merge( $params, $optParams );
		return $this->call( 'create', array( $params ), \PostSMTP\Vendor\Google\Service\Gmail\Label::class );
	}
	/**
	 * Immediately and permanently deletes the specified label and removes it from
	 * any messages and threads that it is applied to. (labels.delete)
	 *
	 * @param string $userId The user's email address. The special value `me` can be
	 * used to indicate the authenticated user.
	 * @param string $id The ID of the label to delete.
	 * @param array  $optParams Optional parameters.
	 */
	public function delete( $userId, $id, $optParams = array() ) {
		$params = array(
			'userId' => $userId,
			'id'     => $id,
		);
		$params = \array_merge( $params, $optParams );
		return $this->call( 'delete', array( $params ) );
	}
	/**
	 * Gets the specified label. (labels.get)
	 *
	 * @param string $userId The user's email address. The special value `me` can be
	 * used to indicate the authenticated user.
	 * @param string $id The ID of the label to retrieve.
	 * @param array  $optParams Optional parameters.
	 * @return Label
	 */
	public function get( $userId, $id, $optParams = array() ) {
		$params = array(
			'userId' => $userId,
			'id'     => $id,
		);
		$params = \array_merge( $params, $optParams );
		return $this->call( 'get', array( $params ), \PostSMTP\Vendor\Google\Service\Gmail\Label::class );
	}
	/**
	 * Lists all labels in the user's mailbox. (labels.listUsersLabels)
	 *
	 * @param string $userId The user's email address. The special value `me` can be
	 * used to indicate the authenticated user.
	 * @param array  $optParams Optional parameters.
	 * @return ListLabelsResponse
	 */
	public function listUsersLabels( $userId, $optParams = array() ) {
		$params = array( 'userId' => $userId );
		$params = \array_merge( $params, $optParams );
		return $this->call( 'list', array( $params ), \PostSMTP\Vendor\Google\Service\Gmail\ListLabelsResponse::class );
	}
	/**
	 * Patch the specified label. (labels.patch)
	 *
	 * @param string $userId The user's email address. The special value `me` can be
	 * used to indicate the authenticated user.
	 * @param string $id The ID of the label to update.
	 * @param Label  $postBody
	 * @param array  $optParams Optional parameters.
	 * @return Label
	 */
	public function patch( $userId, $id, \PostSMTP\Vendor\Google\Service\Gmail\Label $postBody, $optParams = array() ) {
		$params = array(
			'userId'   => $userId,
			'id'       => $id,
			'postBody' => $postBody,
		);
		$params = \array_merge( $params, $optParams );
		return $this->call( 'patch', array( $params ), \PostSMTP\Vendor\Google\Service\Gmail\Label::class );
	}
	/**
	 * Updates the specified label. (labels.update)
	 *
	 * @param string $userId The user's email address. The special value `me` can be
	 * used to indicate the authenticated user.
	 * @param string $id The ID of the label to update.
	 * @param Label  $postBody
	 * @param array  $optParams Optional parameters.
	 * @return Label
	 */
	public function update( $userId, $id, \PostSMTP\Vendor\Google\Service\Gmail\Label $postBody, $optParams = array() ) {
		$params = array(
			'userId'   => $userId,
			'id'       => $id,
			'postBody' => $postBody,
		);
		$params = \array_merge( $params, $optParams );
		return $this->call( 'update', array( $params ), \PostSMTP\Vendor\Google\Service\Gmail\Label::class );
	}
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias( \PostSMTP\Vendor\Google\Service\Gmail\Resource\UsersLabels::class, 'PostSMTP\\Vendor\\Google_Service_Gmail_Resource_UsersLabels' );
