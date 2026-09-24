# 1. Build Stage: Use the DHI Debian dev image to install tools and build your web assets
FROM dhi.io/httpd:2.4.68-debian13-dev AS builder

WORKDIR /app

ARG HTTPD_CERT_SUBJ

# Create CA Certificates
RUN mkdir -p /usr/local/share/ca-certificates/
RUN openssl genrsa -out /app/my-internal-ca.key 2048
RUN openssl req -x509 -new -nodes -key /app/my-internal-ca.key -sha256 -days 1826 -out /usr/local/share/ca-certificates/my-internal-ca.crt  -subj ${HTTPD_CERT_SUBJ}



# 2. Final Stage: Copy the compiled site into a minimal production image
FROM dhi.io/httpd:2.4.68-debian13-dev

# Copy config files from the builder stage into production system
USER root
RUN mkdir -p /usr/local/share/ca-certificates/
COPY --from=builder /usr/local/share/ca-certificates/my-internal-ca.crt /usr/local/share/ca-certificates/
RUN update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

USER 65532
RUN mkdir -p /usr/local/apache2/conf/certs

EXPOSE 8080 443

ENTRYPOINT ["httpd-foreground"]
