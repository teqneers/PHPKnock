variable "IMAGE_NAME" {
    default = "ghcr.io/teqneers/phpknock"
}
variable "IMAGE_TAG" {
    default = "dev"
}
variable "IMAGE_TAG_LATEST" {
    default = "latest"
}

target "_base" {
    context    = "."
    dockerfile = "docker/Dockerfile"
    platforms  = ["linux/amd64"]
}

target "dev" {
    inherits = ["_base"]
    target   = "development"
    tags     = ["${IMAGE_NAME}:dev"]
}

target "default" {
    inherits = ["_base"]
    target   = "production"
    contexts = {
        app = "."
    }
    tags       = ["${IMAGE_NAME}:${IMAGE_TAG}", "${IMAGE_NAME}:${IMAGE_TAG_LATEST}"]
    cache-from = ["${IMAGE_NAME}:${IMAGE_TAG_LATEST}"]
}
