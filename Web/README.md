# Rescue Centre Lite

Rescue Centre Lite is the open/local-install edition of the Rescue Centre patient management system.

This repository is intended to provide the core rescue workflow without hosted-only services, private admin tooling, payments, or cross-centre infrastructure.

## Current Status

Early scaffold. The next step is to extract the stable core from the full `new/` application into this clean structure.

## Intended Lite Scope

- Local install
- Centre setup
- User login and roles
- Patient admission
- My Patients view
- Care notes and core patient records
- Basic archive/reporting
- Optional modules only where they do not require hosted services

## Not Included In Lite

- SaaS billing/payment flows
- Hosted admin tools
- Cross-centre networks/friendships
- Hosted module marketplace
- Private operational scripts

## Install

The installer will live at `/install/` and will create `config/config.php` from `config/config.example.php`.
