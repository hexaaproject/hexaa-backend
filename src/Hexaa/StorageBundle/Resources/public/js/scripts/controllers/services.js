'use strict';

angular.module('hexaaApp', ['ui.bootstrap']).controller('ServiceCtrl', ['$scope', '$window', '$timeout', function($scope, $window, $timeout) {
	/*
		MOCKUPS: these objects have to be fetched from server
	*/
	$scope.services = [
		{
			managers: [
				{ id: 1, name: 'Manager1' },
				{ id: 2, name: 'Manager2' }
			],
			properties: {
				name: 'Service1',
				url: 'http://sp.service1.com',
				samlSpEntityId: 'Service1 samlSpEntityId',
				description: 'Service1 description'
			},
			attributeSpecifications: [
				{
					name: 'Attribute1',
					attribute: 'attribute value'
				}
			],
			entitlements: [
				{
					id: 1,
					name: 'Entitlement1',
					description: 'Entitlement1 description',
					eduPersonEntitlement: 'Entitlement1 eduPersonEntitlement'
				},
				{
					id: 2,
					name: 'Entitlement2',
					description: 'Entitlement2 description',
					eduPersonEntitlement: 'Entitlement2 eduPersonEntitlement'
				}
			],
			entitlementPacks: [
				{
					name: 'Entitlementpack1',
					description: 'Entitlementpack1 description',
					type: 'Entitlementpack1 type',
					token: 'Entitlementpack1 token',
					entitlements: [
						{ id: 1, name: 'Entitlement1' }
					]
				}
			]
		}
	];

	$scope.managers = [
		{ id: 1, name: 'Manager1' },
		{ id: 2, name: 'Manager2' },
		{ id: 3, name: 'Manager3' },
		{ id: 4, name: 'Manager4' }
	];

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

	/*
		INIT
	*/
	/* initial collapse status */
	$scope.isCollapsed = [
		false, false, false, false, false
	];
	/* initial selected service is the first */
	$scope.selectedService = 0;
	/* at first we dont create a service */
	$scope.creatingNewService = false;
	/* service tab is active */
	$scope.active = true;
	/* Shared object with suggest boxes */
	$scope.suggestBoxData = {};

	/* new entitlement */
	$scope.entitlementTemplate = {
		name: '',
		description: '',
		eduPersonEntitlement: ''
	};
	$scope.newEntitlement = JSON.parse(JSON.stringify($scope.entitlementTemplate));

	/* new entitlementPack */
	$scope.entitlementPackTemplate = {
		name: '',
		description: '',
		type: '',
		token: '',
		entitlements: []
	};
	$scope.newEntitlementPack = JSON.parse(JSON.stringify($scope.entitlementPackTemplate));

	/* new service */
	$scope.serviceTemplate = {
			managers: [],
			properties: {
				name: '',
				url: '',
				samlSpEntityId: '',
				description: ''
			},
			attributeSpecifications: [],
			entitlements: [],
			entitlementPacks: []
	};
	$scope.newService = JSON.parse(JSON.stringify($scope.serviceTemplate));

	$scope.selectService = function(idx) {
		if (idx === -1) {
			$scope.creatingNewService = true;
		} else {
			$scope.creatingNewService = false;
			$scope.selectedService = idx;
		}
	};

	$scope.collapse = function(idx) {
		$scope.isCollapsed[idx] = !$scope.isCollapsed[idx];
	};

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

	/* Managers */
	$scope.addManagers = function() {
		angular.forEach($scope.suggestBoxData['newChosenManagers'], function(newManager) {
			var canBeAdded = true;
			angular.forEach($scope.services[$scope.selectedService].managers, function(manager) {
				if (manager.id === newManager.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newManager);
			}
		}, $scope.services[$scope.selectedService].managers);
	};

	$scope.removeManagers = function() {
		angular.forEach($scope.suggestBoxData['oldChosenManagers'], function(oldManager) {
			angular.forEach($scope.services[$scope.selectedService].managers, function(manager, i) {
				if (oldManager.id === manager.id) {
					this.splice(i, 1);
				}
			}, $scope.services[$scope.selectedService].managers);
		});
	};

	/* Entitlements */
	$scope.addNewEntitlement = function() {
		alert("ADD NEW ENTITLEMENT!");
		$scope.services[$scope.selectedService].entitlements.push($scope.newEntitlement);
		$scope.newEntitlement = JSON.parse(JSON.stringify($scope.entitlementTemplate));
	};

	$scope.deleteEntitlement = function(idx) {
		$scope.services[$scope.selectedService].entitlements.splice(idx, 1);
	};

	/* Entitlementpacks */
	$scope.addEntitlements = function(idx) {
		angular.forEach($scope.suggestBoxData['newChosenEntitlements'], function(newEntitlement) {
			var canBeAdded = true;
			angular.forEach($scope.services[$scope.selectedService].entitlementPacks[idx].entitlements, function(entitlement) {
				if (entitlement.id === newEntitlement.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newEntitlement);
			}
		}, $scope.services[$scope.selectedService].entitlementPacks[idx].entitlements);
	};

	$scope.removeEntitlements = function(idx) {
		angular.forEach($scope.suggestBoxData['oldChosenEntitlements'], function(oldEntitlement) {
			angular.forEach($scope.services[$scope.selectedService].entitlementPacks[idx].entitlements, function(entitlement, idx2) {
				if (oldEntitlement.id === entitlement.id) {
					this.splice(idx2, 1);
				}
			}, $scope.services[$scope.selectedService].entitlementPacks[idx].entitlements);
		});
	};

	$scope.addNewEntitlementPack = function() {
		alert("ADD NEW ENTITLEMENTPACK!");
		$scope.services[$scope.selectedService].entitlementPacks.push($scope.newEntitlementPack);
		$scope.newEntitlementPack = JSON.parse(JSON.stringify($scope.entitlementPackTemplate));
	};

	$scope.deleteEntitlementPack = function(idx) {
		$scope.services[$scope.selectedService].entitlementPacks.splice(idx, 1);
	};

	/* Current service */
	$scope.saveService = function() {
		$scope.addAlert('success', 'Service: ' + $scope.services[$scope.selectedService].properties.name + ' saved!');
	};

	$scope.deleteService = function() {
		$scope.addAlert('danger', 'Service: ' + $scope.services[$scope.selectedService].properties.name + ' deleted!');
		$scope.services.splice($scope.selectedService, 1);
		$scope.selectService($scope.services.length - 1);
	};

	/* New organization */
	$scope.addNewService = function() {
		$scope.addAlert('success', 'Service: ' + $scope.newService.properties.name + ' created!');
		$scope.services.push($scope.newService);
		$scope.newService = JSON.parse(JSON.stringify($scope.serviceTemplate));
	};
}]);
