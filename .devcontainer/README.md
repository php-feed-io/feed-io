# Devcontainer PHP Version

This devcontainer uses the official Dev Containers PHP image and supports switching versions by changing the image tag.

## Change PHP version

1. Edit `.devcontainer/devcontainer.json`
2. Update:

```json
"image": "mcr.microsoft.com/devcontainers/php:8.5"
```

Use any valid `mcr.microsoft.com/devcontainers/php:<version>` tag, for example `8.3`, `8.4`, or `8.5`.

3. In VS Code, run: **Dev Containers: Rebuild Container**

## Notes

- No custom `.devcontainer/Dockerfile` is needed for version changes.
- If a version tag is unavailable on MCR, the container creation will fail until a valid tag is used.
