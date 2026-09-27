/**
 * Book Form Validation - For Add Book & Edit Book Pages
 * Version: 4.0
 * Rules:
 * - Author: Only letters and spaces
 * - Year: 1900 to current year
 * - ISBN: Proper format with hyphens (e.g., 978-0-306-40615-7 or 0-306-40615-7)
 */

(function() {
    'use strict';
    
    console.log('Book Validation JS v4.0 loaded - ' + new Date().toLocaleTimeString());
    
    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - initializing validation');
        
        // Find the form
        const form = document.querySelector('form');
        if (!form) {
            console.log('No form found on this page');
            return;
        }
        
        console.log('Form found - setting up validation');
        
        // Get form fields
        const titleInput = form.querySelector('[name="title"]');
        const authorInput = form.querySelector('[name="author"]');
        const categorySelect = form.querySelector('[name="category"]');
        const isbnInput = form.querySelector('[name="isbn"]');
        const publisherInput = form.querySelector('[name="publisher"]');
        const yearInput = form.querySelector('[name="year"]');
        const totalCopiesInput = form.querySelector('[name="total_copies"]');
        const shelfLocationInput = form.querySelector('[name="shelf_location"]');
        const imageInput = form.querySelector('[name="image"]');
        
        // Function to create error container
        function createErrorContainer(inputField, errorId) {
            if (!inputField) return null;
            
            let errorDiv = document.getElementById(errorId);
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = errorId;
                errorDiv.className = 'field-error';
                errorDiv.style.cssText = 'color: #e74c3c; font-size: 11px; margin-top: 2px; display: none;';
                inputField.parentNode.insertBefore(errorDiv, inputField.nextSibling);
            }
            return errorDiv;
        }
        
        // ========== VALIDATION FUNCTIONS ==========
        
        // 1. TITLE Validation
        function validateTitle() {
            if (!titleInput) return true;
            
            const errorContainer = createErrorContainer(titleInput, 'title-error');
            const title = titleInput.value.trim();
            
            if (!title) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Book title is required';
                    errorContainer.style.display = 'block';
                }
                titleInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (title.length < 2) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Title must be at least 2 characters';
                    errorContainer.style.display = 'block';
                }
                titleInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (title.length > 255) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Title cannot exceed 255 characters';
                    errorContainer.style.display = 'block';
                }
                titleInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            titleInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // 2. AUTHOR Validation - ONLY letters and spaces
        function validateAuthor() {
            if (!authorInput) return true;
            
            const errorContainer = createErrorContainer(authorInput, 'author-error');
            const author = authorInput.value.trim();
            
            if (!author) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Author name is required';
                    errorContainer.style.display = 'block';
                }
                authorInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (author.length < 2) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Author name must be at least 2 characters';
                    errorContainer.style.display = 'block';
                }
                authorInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (author.length > 255) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Author name cannot exceed 255 characters';
                    errorContainer.style.display = 'block';
                }
                authorInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            // STRICT VALIDATION: Only letters (A-Z, a-z) and spaces allowed
            const strictAuthorRegex = /^[A-Za-z\s]+$/;
            
            if (!strictAuthorRegex.test(author)) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Author name must contain ONLY letters and spaces (no numbers, dots, hyphens, or special characters)';
                    errorContainer.style.display = 'block';
                }
                authorInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            // Check for multiple consecutive spaces
            if (author.includes('  ')) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Author name cannot have multiple consecutive spaces';
                    errorContainer.style.display = 'block';
                }
                authorInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            authorInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // 3. CATEGORY Validation
        function validateCategory() {
            if (!categorySelect) return true;
            
            const errorContainer = createErrorContainer(categorySelect, 'category-error');
            const category = categorySelect.value;
            
            if (!category) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Please select a category';
                    errorContainer.style.display = 'block';
                }
                categorySelect.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            categorySelect.style.borderColor = '#27ae60';
            return true;
        }
        
        // 4. ISBN Validation - Format like: 978-0-306-40615-7 or 0-306-40615-7
        function validateISBN() {
            if (!isbnInput) return true;
            
            const errorContainer = createErrorContainer(isbnInput, 'isbn-error');
            let isbn = isbnInput.value.trim();
            
            // ISBN is optional
            if (!isbn) {
                if (errorContainer) errorContainer.style.display = 'none';
                isbnInput.style.borderColor = '#ddd';
                return true;
            }
            
            // Remove "ISBN" prefix if user typed it
            isbn = isbn.replace(/^ISBN\s+/i, '');
            
            // Regular expression for ISBN-10 with hyphens: 0-306-40615-7
            // Pattern: groups of digits separated by hyphens, total 10 digits (last can be X)
            const isbn10HyphenRegex = /^\d{1,5}-\d{1,7}-\d{1,7}-[\dX]$/i;
            
            // Regular expression for ISBN-13 with hyphens: 978-0-306-40615-7
            // Pattern: starts with 978 or 979, then hyphens, total 13 digits
            const isbn13HyphenRegex = /^97[89]-\d{1,5}-\d{1,7}-\d{1,7}-\d$/;
            
            // Extract just the digits for validation
            const digitsOnly = isbn.replace(/-/g, '');
            
            let isValid = false;
            let errorMessage = '';
            let formattedIsbn = isbn;
            
            // Check ISBN-13 format (13 digits, starts with 978 or 979)
            if (digitsOnly.length === 13 && /^97[89]/.test(digitsOnly)) {
                if (validateISBN13Checksum(digitsOnly)) {
                    isValid = true;
                    // Format nicely with hyphens if not already properly formatted
                    if (!isbn13HyphenRegex.test(isbn)) {
                        formattedIsbn = formatISBN13(digitsOnly);
                    }
                } else {
                    errorMessage = '❌ Invalid ISBN-13 checksum';
                }
            }
            // Check ISBN-10 format (10 digits, last can be X)
            else if (digitsOnly.length === 10 || (digitsOnly.length === 10 && /^\d{9}X$/i.test(digitsOnly))) {
                if (validateISBN10Checksum(digitsOnly)) {
                    isValid = true;
                    // Format nicely with hyphens if not already properly formatted
                    if (!isbn10HyphenRegex.test(isbn)) {
                        formattedIsbn = formatISBN10(digitsOnly);
                    }
                } else {
                    errorMessage = '❌ Invalid ISBN-10 checksum';
                }
            }
            else if (digitsOnly.length === 13 && !/^97[89]/.test(digitsOnly)) {
                errorMessage = '❌ ISBN-13 must start with 978 or 979';
            }
            else if (digitsOnly.length === 10 && !/^\d{9}X$/i.test(digitsOnly) && !/^\d{10}$/.test(digitsOnly)) {
                errorMessage = '❌ ISBN-10 must be 10 digits (last character can be X)';
            }
            else {
                errorMessage = '❌ ISBN must be in format: 978-0-306-40615-7 (ISBN-13) or 0-306-40615-7 (ISBN-10)';
            }
            
            if (!isValid) {
                if (errorContainer) {
                    errorContainer.textContent = errorMessage;
                    errorContainer.style.display = 'block';
                }
                isbnInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            // Update input with properly formatted ISBN
            if (isbnInput.value !== formattedIsbn) {
                isbnInput.value = formattedIsbn;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            isbnInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // Format ISBN-13 with hyphens: 978-0-306-40615-7
        function formatISBN13(isbn) {
            // Remove any existing hyphens
            const clean = isbn.replace(/-/g, '');
            if (clean.length !== 13) return isbn;
            
            // Common grouping: 978-0-306-40615-7
            return `${clean.substring(0, 3)}-${clean.substring(3, 4)}-${clean.substring(4, 7)}-${clean.substring(7, 12)}-${clean.substring(12)}`;
        }
        
        // Format ISBN-10 with hyphens: 0-306-40615-7
        function formatISBN10(isbn) {
            // Remove any existing hyphens
            const clean = isbn.replace(/-/g, '');
            if (clean.length !== 10) return isbn;
            
            // Common grouping for ISBN-10
            if (clean.startsWith('0') || clean.startsWith('1')) {
                return `${clean.substring(0, 1)}-${clean.substring(1, 4)}-${clean.substring(4, 9)}-${clean.substring(9)}`;
            }
            return `${clean.substring(0, 3)}-${clean.substring(3, 6)}-${clean.substring(6, 9)}-${clean.substring(9)}`;
        }
        
        // ISBN-10 Checksum Validation
        function validateISBN10Checksum(isbn) {
            const clean = isbn.replace(/-/g, '');
            if (clean.length !== 10) return false;
            
            let sum = 0;
            for (let i = 0; i < 9; i++) {
                const digit = parseInt(clean.charAt(i));
                if (isNaN(digit)) return false;
                sum += digit * (i + 1);
            }
            
            const checksum = sum % 11;
            const lastChar = clean.charAt(9).toUpperCase();
            
            if (checksum === 10) {
                return lastChar === 'X';
            }
            return parseInt(lastChar) === checksum;
        }
        
        // ISBN-13 Checksum Validation
        function validateISBN13Checksum(isbn) {
            const clean = isbn.replace(/-/g, '');
            if (clean.length !== 13) return false;
            
            let sum = 0;
            for (let i = 0; i < 12; i++) {
                const digit = parseInt(clean.charAt(i));
                if (isNaN(digit)) return false;
                if (i % 2 === 0) {
                    sum += digit;
                } else {
                    sum += digit * 3;
                }
            }
            const checksum = (10 - (sum % 10)) % 10;
            return parseInt(clean.charAt(12)) === checksum;
        }
        
        // 5. PUBLISHER Validation (optional)
        function validatePublisher() {
            if (!publisherInput) return true;
            
            const errorContainer = createErrorContainer(publisherInput, 'publisher-error');
            const publisher = publisherInput.value.trim();
            
            if (!publisher) {
                if (errorContainer) errorContainer.style.display = 'none';
                publisherInput.style.borderColor = '#ddd';
                return true;
            }
            
            if (publisher.length > 255) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Publisher name cannot exceed 255 characters';
                    errorContainer.style.display = 'block';
                }
                publisherInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            publisherInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // 6. YEAR Validation - 1900 to current year
        function validateYear() {
            if (!yearInput) return true;
            
            const errorContainer = createErrorContainer(yearInput, 'year-error');
            const year = yearInput.value;
            const currentYear = new Date().getFullYear();
            
            // Year is optional
            if (!year) {
                if (errorContainer) errorContainer.style.display = 'none';
                yearInput.style.borderColor = '#ddd';
                return true;
            }
            
            const yearNum = parseInt(year);
            
            if (isNaN(yearNum) || year.length !== 4) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Please enter a valid 4-digit year (e.g., 2020)';
                    errorContainer.style.display = 'block';
                }
                yearInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (yearNum < 1900) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Year must be 1900 or later';
                    errorContainer.style.display = 'block';
                }
                yearInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (yearNum > currentYear) {
                if (errorContainer) {
                    errorContainer.textContent = `❌ Year cannot be in the future (max ${currentYear})`;
                    errorContainer.style.display = 'block';
                }
                yearInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            yearInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // 7. TOTAL COPIES Validation
        function validateTotalCopies() {
            if (!totalCopiesInput) return true;
            
            const errorContainer = createErrorContainer(totalCopiesInput, 'totalcopies-error');
            const totalCopies = parseInt(totalCopiesInput.value);
            
            if (!totalCopiesInput.value || isNaN(totalCopies)) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Please enter the total number of copies';
                    errorContainer.style.display = 'block';
                }
                totalCopiesInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (totalCopies < 1) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Total copies must be at least 1';
                    errorContainer.style.display = 'block';
                }
                totalCopiesInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (totalCopies > 99999) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Total copies seems too high (max 99,999)';
                    errorContainer.style.display = 'block';
                }
                totalCopiesInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            totalCopiesInput.style.borderColor = '#27ae60';
            
            // Auto-update available field if it exists (for add book page)
            const availableInput = form.querySelector('[name="available"]');
            if (availableInput && totalCopiesInput.value) {
                availableInput.value = totalCopiesInput.value;
            }
            
            return true;
        }
        
        // 8. SHELF LOCATION Validation
        function validateShelfLocation() {
            if (!shelfLocationInput) return true;
            
            const errorContainer = createErrorContainer(shelfLocationInput, 'shelflocation-error');
            const shelfLocation = shelfLocationInput.value.trim();
            
            if (!shelfLocation) {
                if (errorContainer) errorContainer.style.display = 'none';
                shelfLocationInput.style.borderColor = '#ddd';
                return true;
            }
            
            if (shelfLocation.length > 100) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Shelf location cannot exceed 100 characters';
                    errorContainer.style.display = 'block';
                }
                shelfLocationInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            shelfLocationInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // 9. IMAGE Validation
        function validateImage() {
            if (!imageInput) return true;
            
            const errorContainer = createErrorContainer(imageInput, 'image-error');
            
            if (!imageInput.files || imageInput.files.length === 0) {
                if (errorContainer) errorContainer.style.display = 'none';
                return true;
            }
            
            const file = imageInput.files[0];
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 2 * 1024 * 1024;
            
            if (!allowedTypes.includes(file.type)) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Only JPG, PNG, GIF, and WEBP images are allowed';
                    errorContainer.style.display = 'block';
                }
                imageInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (file.size > maxSize) {
                if (errorContainer) {
                    errorContainer.textContent = '❌ Image size must be less than 2MB';
                    errorContainer.style.display = 'block';
                }
                imageInput.style.borderColor = '#e74c3c';
                return false;
            }
            
            if (errorContainer) errorContainer.style.display = 'none';
            imageInput.style.borderColor = '#27ae60';
            return true;
        }
        
        // ========== ATTACH EVENT LISTENERS ==========
        
        if (titleInput) {
            titleInput.addEventListener('input', validateTitle);
            titleInput.addEventListener('blur', validateTitle);
        }
        
        if (authorInput) {
            authorInput.addEventListener('input', validateAuthor);
            authorInput.addEventListener('blur', validateAuthor);
        }
        
        if (categorySelect) {
            categorySelect.addEventListener('change', validateCategory);
            categorySelect.addEventListener('blur', validateCategory);
        }
        
        if (isbnInput) {
            isbnInput.addEventListener('input', validateISBN);
            isbnInput.addEventListener('blur', validateISBN);
        }
        
        if (publisherInput) {
            publisherInput.addEventListener('input', validatePublisher);
            publisherInput.addEventListener('blur', validatePublisher);
        }
        
        if (yearInput) {
            yearInput.addEventListener('input', validateYear);
            yearInput.addEventListener('blur', validateYear);
        }
        
        if (totalCopiesInput) {
            totalCopiesInput.addEventListener('input', validateTotalCopies);
            totalCopiesInput.addEventListener('blur', validateTotalCopies);
        }
        
        if (shelfLocationInput) {
            shelfLocationInput.addEventListener('input', validateShelfLocation);
            shelfLocationInput.addEventListener('blur', validateShelfLocation);
        }
        
        if (imageInput) {
            imageInput.addEventListener('change', validateImage);
        }
        
        // ========== FORM SUBMISSION ==========
        
        form.addEventListener('submit', function(e) {
            console.log('Form submission - validating...');
            
            const isTitleValid = validateTitle();
            const isAuthorValid = validateAuthor();
            const isCategoryValid = validateCategory();
            const isISBNValid = validateISBN();
            const isPublisherValid = validatePublisher();
            const isYearValid = validateYear();
            const isTotalCopiesValid = validateTotalCopies();
            const isShelfLocationValid = validateShelfLocation();
            const isImageValid = validateImage();
            
            const isValid = isTitleValid && isAuthorValid && isCategoryValid && 
                            isISBNValid && isPublisherValid && isYearValid && 
                            isTotalCopiesValid && isShelfLocationValid && isImageValid;
            
            if (!isValid) {
                e.preventDefault();
                console.log('Validation failed - form not submitted');
                
                const firstError = document.querySelector('.field-error[style*="display: block"]');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                const errors = [];
                document.querySelectorAll('.field-error').forEach(function(el) {
                    if (el.style.display === 'block' && el.textContent) {
                        errors.push(el.textContent);
                    }
                });
                
                if (errors.length > 0) {
                    alert('Please fix the following errors:\n\n' + errors.join('\n'));
                }
            } else {
                console.log('Validation passed - submitting form');
            }
        });
        
        // Add helpful placeholders and hints
        if (isbnInput) {
            isbnInput.placeholder = '978-0-306-40615-7 or 0-306-40615-7';
            // Add hint below ISBN field
            const hint = document.createElement('div');
            hint.className = 'form-hint';
            hint.style.cssText = 'font-size: 10px; color: #7f8c8d; margin-top: 2px;';
            hint.innerHTML = '📚 Format: 978-0-306-40615-7 (ISBN-13) or 0-306-40615-7 (ISBN-10)';
            isbnInput.parentNode.insertBefore(hint, isbnInput.nextSibling);
        }
        
        if (yearInput) {
            yearInput.placeholder = `1900 - ${new Date().getFullYear()}`;
        }
        
        if (authorInput) {
            authorInput.placeholder = 'e.g., John Smith (letters and spaces only)';
        }
        
        console.log('Validation setup complete');
    });
})();