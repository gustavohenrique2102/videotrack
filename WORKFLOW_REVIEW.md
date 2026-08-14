# GitHub workflow review

This document records the review performed before publishing the VideoTrack 1.3.0 proposal.

## Continuous integration

The previous CI workflow had a useful Moodle 4.5 matrix, but it had four important gaps:

1. It did not execute PHPUnit, so regressions in progress calculation and source replacement
   could pass the workflow.
2. The MariaDB service declared MYSQL_USER as root. The official image reserves the root
   account and can reject that configuration.
3. The PHP setup referenced an undefined matrix.extensions value.
4. Moodle 5.2 was not part of the original test matrix.

The revised workflow:

* runs on pushes, pull requests, and manual dispatches;
* grants read-only repository permission;
* cancels superseded runs on the same branch;
* sets a 35-minute job timeout;
* tests Moodle 4.5 and 5.2 with PHP 8.1, 8.3, and 8.4;
* covers both MariaDB 10.11 and PostgreSQL 16;
* runs PHP lint, Moodle CodeSniffer, PHPDoc, plugin validation, Mustache lint,
  JavaScript/CSS checks, and PHPUnit;
* keeps PHP Mess Detector informational because it can report legacy complexity that is not
  a release blocker.

Both database services are started for every matrix entry because GitHub Actions does not
support conditional service definitions. This costs some startup time but keeps one readable
job definition. Splitting the databases into separate jobs is an optional future optimisation.

## Moodle.org release workflow

The previous release workflow could call the Moodle.org API, but it interpolated a manually
entered tag directly into shell source, did not validate the tag or ZIP endpoint, wrote the
API response to GITHUB_OUTPUT as an unsafe single line, and considered an HTTP 200 response
successful even when the JSON contained a Moodle exception.

The revised workflow:

* uses read-only repository permissions and a ten-minute timeout;
* accepts only semantic version tags beginning with v;
* passes GitHub expressions through environment variables instead of shell interpolation;
* verifies that the GitHub tag archive is reachable;
* uses curl fail-with-body and validates the response with jq;
* fails when Moodle returns an exception or error code;
* writes multiline output using the documented delimiter format;
* serialises Moodle.org publications with a concurrency group;
* uses a protected GitHub environment named moodle-org.

## Required repository configuration

The CI workflow needs no repository secrets.

The Moodle.org publication workflow remains inactive until the repository owner:

1. creates or reviews the moodle-org environment under repository settings;
2. adds the MOODLE_ORG_TOKEN environment secret;
3. confirms that mod_videotrack is registered in the corresponding Moodle.org account;
4. creates an existing Git tag such as v1.3.0;
5. publishes a GitHub Release or manually runs the workflow with that tag.

Do not copy a Moodle.org token into source files, workflow YAML, issues, or Actions logs.

## Residual risks and recommendations

* Third-party actions currently use major release tags. For a stricter supply-chain policy,
  pin actions/checkout and shivammathur/setup-php to reviewed commit SHAs and update them
  through Dependabot.
* The release workflow registers a version on an external service. Keep environment approval
  enabled so publication requires a human reviewer.
* Enable branch protection on main and require all CI matrix checks before merging.
* Enable Dependabot alerts, secret scanning, and push protection when available.
* The first CI run should be observed because Moodle 5.2 is a newer branch and upstream
  dependency availability can change independently of this plugin.
