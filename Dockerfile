# Use a minimal Python image as the base
FROM python:3.11-slim

# Install necessary OS packages: pip is for Ansible, openssh-client is for SSH connections to managed nodes
RUN apt-get update && \
    apt-get install -y openssh-client git && \
    rm -rf /var/lib/apt/lists/*

# Install Ansible using pip, which is the official method
RUN pip install --no-cache-dir ansible

# Set the working directory inside the container to mount your project files
WORKDIR /ansible

# Define a default command to keep the container running if needed, though we will mostly use 'run'
CMD ["/bin/bash"]