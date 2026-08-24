# Private Certificates

Put the Aiven MySQL CA certificate in this folder for local development, for example:

```text
private/certs/aiven-ca.pem
```

Then set:

```env
DB_SSL_CA=private/certs/aiven-ca.pem
```

Do not commit certificate files or private keys. This folder is ignored except for this README.
