'use strict';

angular.module('hexaaApp', ['ui.bootstrap']).controller('ProfileCtrl', ['$scope', '$window', '$timeout', function($scope, $window, $timeout) {
	/*
		MOCKUPS: these objects have to be fetched from server
	*/
	$scope.user = {
		username: 'Member0',
		email: 'member0@example.com',
		attributes: [
			{
				name: 'Attribute1',
				attribute: 'Attribute1 value',
				isDefault: true
			},
			{
				name: 'Attribute2',
				attribute: 'Attribute2 value',
				isDefault: false
			}
		],
		entitlements: [
			{
				name: 'Entitlement1',
				description: 'Entitlement1 description',
				eduPersonEntitlement: 'Entitlement1 eduPersonEntitlement'
			},
			{
				name: 'Entitlement2',
				description: 'Entitlement2 description',
				eduPersonEntitlement: 'Entitlement2 eduPersonEntitlement'
			}
		]
	};

	/* profile tab is active */
	$scope.active = true;

	$scope.goTo = function(path) {
		$window.location.href = path;
	};

	$scope.logout = function() {
		alert("LOGOUT!");
	};

	/* ALERTS */
	$scope.alerts = [];

	$scope.addAlert = function(type, msg) {
		$scope.alerts.push({type: type, msg: msg});
		$timeout(function() {
			$('.alert' + ($scope.alerts.length - 1)).fadeOut();
			$scope.alerts.shift();
		}, 3000);
	};

	$scope.closeAlert = function(index) {
		$scope.alerts.splice(index, 1);
	};

	/*
		GUI LOGIC
	*/
	$scope.saveProfile = function() {
		$scope.addAlert('success', 'Profile saved!');
	};
}]);
