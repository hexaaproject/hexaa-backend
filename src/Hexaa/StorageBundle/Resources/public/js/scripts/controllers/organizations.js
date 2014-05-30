'use strict';

angular.module('hexaaApp', ['ui.bootstrap']).controller('OrganizationCtrl', ['$scope', '$window', '$timeout', function($scope, $window, $timeout) {
	/*
		MOCKUPS: these objects have to be fetched from server
	*/
	$scope.organizations = [
		{
			properties: {
				name: 'Organization1',
				description: 'Organization1 description',
				defaultRole: 'Organization1 default role'
			},
			managers: [
				{ id: 1, name: 'Manager1' },
				{ id: 2, name: 'Manager2' }
			],
			members: [
				{ id: 1, name: 'Member1' },
				{ id: 2, name: 'Member2' }
			],
			connectedEntitlementPacks: [
				{ id: 1, name: 'Entitlementpack1' },
				{ id: 2, name: 'Entitlementpack2' }
			],
			roles: [
				{ 	id: 1,
					properties: {
						name: 'Role1',
						description: 'Role1 description'
					},
					members: [
						{ id: 1, name: 'Member1' }
					],
					entitlements: [
						{ id: 1, name: 'Entitlement1' },
						{ id: 2, name: 'Entitlement2' }
					]
				},
				{ 	id: 2,
					properties: {
						name: 'Role2',
						description: 'Role2 description'
					},
					members: [
						{ id: 1, name: 'Member1' },
						{ id: 2, name: 'Member2' }
					],
					entitlements: [
						{ id: 1, name: 'Entitlement1' },
						{ id: 2, name: 'Entitlement2' }
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

	$scope.members = [
		{ id: 1, name: 'Member1' },
		{ id: 2, name: 'Member2' },
		{ id: 3, name: 'Member3' },
		{ id: 4, name: 'Member4' }
	];

	$scope.entitlements = [
		{ id: 1, name: 'Entitlement1' },
		{ id: 2, name: 'Entitlement2' },
		{ id: 3, name: 'Entitlement3' },
		{ id: 4, name: 'Entitlement4' }
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
		false, false, false, false
	];
	/* initial selected organization is the first */
	$scope.selectedOrganization = 0;
	/* at first we dont create an organization */
	$scope.creatingNewOrganization = false;
	/* organization tab is active */
	$scope.active = true;
	/* Shared object with suggest boxes */
	$scope.suggestBoxData = {};

	/* new role */
	$scope.roleTemplate = {
		properties: {
			name: '',
			description: ''
		},
		members: [],
		entitlements: []
	};
	$scope.newRole = JSON.parse(JSON.stringify($scope.roleTemplate));

	/* new organization */
	$scope.organizationTemplate = {
		properties: {
			name: '',
			description: '',
			defaultRole: ''
		},
		managers: [],
		members: [],
		connectedEntitlementPacks: [],
		role: []
	};
	$scope.newOrganization = JSON.parse(JSON.stringify($scope.organizationTemplate));

	$scope.selectOrganization = function(idx) {
		if (idx === -1) {
			$scope.creatingNewOrganization = true;
		} else {
			$scope.creatingNewOrganization = false;
			$scope.selectedOrganization = idx;
		}
	};

	$scope.goTo = function(path) {
		$window.location.href = path;
	};

	$scope.collapse = function(idx) {
		$scope.isCollapsed[idx] = !$scope.isCollapsed[idx];
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
			angular.forEach($scope.organizations[$scope.selectedOrganization].managers, function(manager) {
				if (manager.id === newManager.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newManager);
			}
		}, $scope.organizations[$scope.selectedOrganization].managers);
	};

	$scope.removeManagers = function() {
		angular.forEach($scope.suggestBoxData['oldChosenManagers'], function(oldManager) {
			angular.forEach($scope.organizations[$scope.selectedOrganization].managers, function(manager, i) {
				if (oldManager.id === manager.id) {
					this.splice(i, 1);
				}
			}, $scope.organizations[$scope.selectedOrganization].managers);
		});
	};

	/* Members */
	$scope.addMembers = function() {
		angular.forEach($scope.suggestBoxData['newChosenMembers'], function(newMember) {
			var canBeAdded = true;
			angular.forEach($scope.organizations[$scope.selectedOrganization].members, function(member) {
				if (member.id === newMember.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newMember);
			}
		}, $scope.organizations[$scope.selectedOrganization].members);
	};

	$scope.removeMembers = function() {
		angular.forEach($scope.suggestBoxData['oldChosenMembers'], function(oldMember) {
			angular.forEach($scope.organizations[$scope.selectedOrganization].members, function(member, idx) {
				if (oldMember.id === member.id) {
					this.splice(idx, 1);
				}
			}, $scope.organizations[$scope.selectedOrganization].members);
		});
	};

	$scope.inviteMembers = function() {
		alert('INVITE MEMBERS!');
	};

	/* Connected entitlementpacks */
	$scope.deleteConnectedEntitlementPack = function(id) {
		angular.forEach($scope.organizations[$scope.selectedOrganization].connectedEntitlementPacks, function(connectedEntitlementPack, idx) {
			if (connectedEntitlementPack.id === id) {
				this.splice(idx, 1);
			}
		}, $scope.organizations[$scope.selectedOrganization].connectedEntitlementPacks);
	};

	$scope.connectNewEntitlementpack = function(token) {
		$scope.addAlert('success', 'Entitlementpack ' + token + ' connected!');
	};

	$scope.addRoleMembers = function(idx) {
		angular.forEach($scope.suggestBoxData['newChosenRoleMembers'], function(newMember) {
			var canBeAdded = true;
			angular.forEach($scope.organizations[$scope.selectedOrganization].roles[idx].members, function(member) {
				if (member.id === newMember.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newMember);
			}
		}, $scope.organizations[$scope.selectedOrganization].roles[idx].members);
	};

	$scope.removeRoleMembers = function(idx) {
		angular.forEach($scope.suggestBoxData['oldChosenRoleMembers'], function(oldMember) {
			angular.forEach($scope.organizations[$scope.selectedOrganization].roles[idx].members, function(member, idx2) {
				if (oldMember.id === member.id) {
					this.splice(idx2, 1);
				}
			}, $scope.organizations[$scope.selectedOrganization].roles[idx].members);
		});
	};

	$scope.addEntitlements = function(idx) {
		angular.forEach($scope.suggestBoxData['newChosenEntitlements'], function(newEntitlement) {
			var canBeAdded = true;
			angular.forEach($scope.organizations[$scope.selectedOrganization].roles[idx].entitlements, function(entitlement) {
				if (entitlement.id === newEntitlement.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newEntitlement);
			}
		}, $scope.organizations[$scope.selectedOrganization].roles[idx].entitlements);
	};

	$scope.removeEntitlements = function(idx) {
		angular.forEach($scope.suggestBoxData['oldChosenEntitlements'], function(oldEntitlement) {
			angular.forEach($scope.organizations[$scope.selectedOrganization].roles[idx].entitlements, function(entitlement, idx2) {
				if (oldEntitlement.id === entitlement.id) {
					this.splice(idx2, 1);
				}
			}, $scope.organizations[$scope.selectedOrganization].roles[idx].entitlements);
		});
	};

	$scope.addRoleMembersToNewRole = function() {
		angular.forEach($scope.suggestBoxData['newChosenRoleMembersToNewRole'], function(newMember) {
			var canBeAdded = true;
			angular.forEach($scope.newRole.members, function(member) {
				if (member.id === newMember.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newMember);
			}
		}, $scope.newRole.members);
	};

	$scope.removeRoleMembersFromNewRole = function() {
		angular.forEach($scope.suggestBoxData['oldChosenRoleMembersFromNewRole'], function(oldMember) {
			angular.forEach($scope.newRole.members, function(member, idx) {
				if (oldMember.id === member.id) {
					this.splice(idx, 1);
				}
			}, $scope.newRole.members);
		});
	};

	$scope.addEntitlementsToNewRole = function() {
		angular.forEach($scope.suggestBoxData['newChosenEntitlementsToNewRole'], function(newEntitlement) {
			var canBeAdded = true;
			angular.forEach($scope.newRole.entitlements, function(entitlement) {
				if (entitlement.id === newEntitlement.id) {
					canBeAdded = false;
				};
			});
			if (canBeAdded) {
				this.push(newEntitlement);
			}
		}, $scope.newRole.entitlements);
	};

	$scope.removeEntitlementsFromNewRole = function() {
		angular.forEach($scope.suggestBoxData['oldChosenEntitlementsFromNewRole'], function(oldEntitlement) {
			angular.forEach($scope.newRole.entitlements, function(entitlement, idx) {
				if (oldEntitlement.id === entitlement.id) {
					this.splice(idx, 1);
				}
			}, $scope.newRole.entitlements);
		});
	};

	$scope.deleteRole = function(idx) {
		$scope.organizations[$scope.selectedOrganization].roles.splice(idx, 1);
	};

	$scope.addNewRole = function() {
		alert("ADD NEW ROLE!");
		$scope.organizations[$scope.selectedOrganization].roles.push($scope.newRole);
		$scope.newRole = JSON.parse(JSON.stringify($scope.roleTemplate));
	};

	/* Current organization */
	$scope.saveOrganization = function() {
		$scope.addAlert('success', 'Organization: ' + $scope.organizations[$scope.selectedOrganization].properties.name + ' saved!');
	};

	$scope.deleteOrganization = function() {
		$scope.addAlert('danger', 'Organization: ' + $scope.organizations[$scope.selectedOrganization].properties.name + ' deleted!');
		$scope.organizations.splice($scope.selectedOrganization, 1);
		$scope.selectOrganization($scope.organizations.length - 1);
	};

	/* New organization */
	$scope.addNewOrganization = function() {
		$scope.addAlert('success', 'Organization: ' + $scope.newOrganization.properties.name + ' created!');
		$scope.organizations.push($scope.newOrganization);
		$scope.newOrganization = JSON.parse(JSON.stringify($scope.organizationTemplate));
	};
}]);
