Version 0.31.0
--------------

**Breaking change:** Linking organizations and services have been changed in this version.
Linking happens through the (aptly named) Link entities.
 
 There is exactly one Link entity between any service and organization,
 which defines that the service linked should receive attributes from the 
 members (remember, that managers are also members) of the linked organization.
 
 Reading, posting or modifying pending links is allowed for the managers of the related service or organization,
 but accepting or modifying the entitlements or entitlement packs of an accepted link
 is only permitted to the service managers.
 
**Breaking change:** Entitlement packs cannot be linked directly anymore. Instead they are to be added to
 Link entities. 
 
**Breaking change:** GET api/organizations/{id}/entitlementpacks now returns entitlementpack entities.

**New function:** Entitlements can be linked now without being put into an entitlement package.

**Deprecated calls:** The calls used by entitlement package linking are deprecated from now on,
 and shall be removed in a future release.
 
 These are:
 
 * **GET** api/entitlementpack/{id}/token
 * **PUT** api/organizations/{id}/entitlementpacks/{token}/token
 * **PUT** api/organizations/{id}/entitlementpacks/{epid}
 * **PUT** api/organizations/{id}/entitlementpacks/{epid}/accept
 * **DELETE** api/organizations/{id}/entitlementpacks/{epid}
 