#!/bin/bash

# Bash script to manage the image compressor plugin structure

# Function to create the directory structure
create_directory_structure() {
  echo "Creating directory structure..."
  mkdir -p image-compressor-plugin/{assets/{js,css},includes,templates}
  touch image-compressor-plugin/{assets/js/admin.js,assets/css/admin.css,includes/{class-image-compressor.php,class-image-handler.php},templates/admin-dashboard.php,image-compressor-plugin.php}
  echo "Directory structure created successfully."
}

# Function to concatenate and format data from all files into a consolidated file
cat_files() {
  output_file="consolidated_file.txt"
  echo "Creating consolidated file: $output_file"
  > $output_file
  for file in $(find image-compressor-plugin -type f); do
    echo "===== $file =====" >> $output_file
    cat "$file" >> $output_file
    echo -e "\n" >> $output_file
  done
  echo "Consolidated file created successfully: $output_file"
}

# Display menu options
echo "Choose an option:"
echo "1) Create Directory"
echo "2) Cat out formatted data of each file to a consolidated file"
read -p "Enter your choice: " choice

case $choice in
  1)
    create_directory_structure
    ;;
  2)
    cat_files
    ;;
  *)
    echo "Invalid choice. Please select 1 or 2."
    ;;
esac
