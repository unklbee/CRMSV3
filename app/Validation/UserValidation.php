<?php

namespace App\Validation;

class UserValidation
{
    /**
     * Simplified validation rules untuk login (efisiensi)
     */
    public static function getLoginRules(): array
    {
        return [
            'identifier' => [
                'label' => 'Username/Email',
                'rules' => 'required|min_length[3]|max_length[100]',
                'errors' => [
                    'required' => 'Username or Email is required',
                    'min_length' => 'Username/Email is too short',
                    'max_length' => 'Username/Email is too long'
                ]
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Password is required'
                ]
            ]
        ];
    }

    /**
     * Comprehensive validation rules untuk registrasi
     */
    public static function getRegistrationRules(): array
    {
        return [
            'username' => [
                'label' => 'Username',
                'rules' => 'required|min_length[3]|max_length[50]|alpha_numeric_punct|is_unique[users.username]',
                'errors' => [
                    'required' => 'Username is required',
                    'min_length' => 'Username must be at least 3 characters',
                    'max_length' => 'Username cannot exceed 50 characters',
                    'alpha_numeric_punct' => 'Username can only contain letters, numbers, and basic punctuation',
                    'is_unique' => 'Username already exists'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[100]|is_unique[users.email]',
                'errors' => [
                    'required' => 'Email is required',
                    'valid_email' => 'Please enter a valid email address',
                    'max_length' => 'Email cannot exceed 100 characters',
                    'is_unique' => 'Email already exists'
                ]
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/]',
                'errors' => [
                    'required' => 'Password is required',
                    'min_length' => 'Password must be at least 8 characters',
                    'max_length' => 'Password cannot exceed 255 characters',
                    'regex_match' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
                ]
            ],
            'password_confirm' => [
                'label' => 'Confirm Password',
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Please confirm your password',
                    'matches' => 'Password confirmation does not match'
                ]
            ],
            'first_name' => [
                'label' => 'First Name',
                'rules' => 'required|min_length[2]|max_length[50]|alpha_space',
                'errors' => [
                    'required' => 'First name is required',
                    'min_length' => 'First name must be at least 2 characters',
                    'max_length' => 'First name cannot exceed 50 characters',
                    'alpha_space' => 'First name can only contain letters and spaces'
                ]
            ],
            'last_name' => [
                'label' => 'Last Name',
                'rules' => 'required|min_length[2]|max_length[50]|alpha_space',
                'errors' => [
                    'required' => 'Last name is required',
                    'min_length' => 'Last name must be at least 2 characters',
                    'max_length' => 'Last name cannot exceed 50 characters',
                    'alpha_space' => 'Last name can only contain letters and spaces'
                ]
            ]
        ];
    }

    /**
     * Optimized validation rules untuk update profile
     */
    public static function getUpdateProfileRules(int $userId): array
    {
        return [
            'username' => [
                'label' => 'Username',
                'rules' => "required|min_length[3]|max_length[50]|alpha_numeric_punct|is_unique[users.username,id,{$userId}]",
                'errors' => [
                    'required' => 'Username is required',
                    'min_length' => 'Username must be at least 3 characters',
                    'max_length' => 'Username cannot exceed 50 characters',
                    'alpha_numeric_punct' => 'Username can only contain letters, numbers, and basic punctuation',
                    'is_unique' => 'Username already exists'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => "required|valid_email|max_length[100]|is_unique[users.email,id,{$userId}]",
                'errors' => [
                    'required' => 'Email is required',
                    'valid_email' => 'Please enter a valid email address',
                    'max_length' => 'Email cannot exceed 100 characters',
                    'is_unique' => 'Email already exists'
                ]
            ],
            'first_name' => [
                'label' => 'First Name',
                'rules' => 'required|min_length[2]|max_length[50]|alpha_space',
                'errors' => [
                    'required' => 'First name is required',
                    'min_length' => 'First name must be at least 2 characters',
                    'max_length' => 'First name cannot exceed 50 characters',
                    'alpha_space' => 'First name can only contain letters and spaces'
                ]
            ],
            'last_name' => [
                'label' => 'Last Name',
                'rules' => 'required|min_length[2]|max_length[50]|alpha_space',
                'errors' => [
                    'required' => 'Last name is required',
                    'min_length' => 'Last name must be at least 2 characters',
                    'max_length' => 'Last name cannot exceed 50 characters',
                    'alpha_space' => 'Last name can only contain letters and spaces'
                ]
            ]
        ];
    }

    /**
     * Simplified change password validation
     */
    public static function getChangePasswordRules(): array
    {
        return [
            'current_password' => [
                'label' => 'Current Password',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Current password is required'
                ]
            ],
            'new_password' => [
                'label' => 'New Password',
                'rules' => 'required|min_length[8]|max_length[255]|differs[current_password]',
                'errors' => [
                    'required' => 'New password is required',
                    'min_length' => 'New password must be at least 8 characters',
                    'max_length' => 'New password cannot exceed 255 characters',
                    'differs' => 'New password must be different from current password'
                ]
            ],
            'confirm_password' => [
                'label' => 'Confirm New Password',
                'rules' => 'required|matches[new_password]',
                'errors' => [
                    'required' => 'Please confirm your new password',
                    'matches' => 'Password confirmation does not match'
                ]
            ]
        ];
    }

    /**
     * Quick validation untuk API endpoints
     */
    public static function getQuickLoginRules(): array
    {
        return [
            'identifier' => 'required|max_length[100]',
            'password' => 'required|max_length[255]'
        ];
    }

    /**
     * Validation untuk forgot password
     */
    public static function getForgotPasswordRules(): array
    {
        return [
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[100]',
                'errors' => [
                    'required' => 'Email is required',
                    'valid_email' => 'Please enter a valid email address',
                    'max_length' => 'Email cannot exceed 100 characters'
                ]
            ]
        ];
    }

    /**
     * Validation untuk reset password
     */
    public static function getResetPasswordRules(): array
    {
        return [
            'token' => [
                'label' => 'Reset Token',
                'rules' => 'required|min_length[32]|max_length[255]',
                'errors' => [
                    'required' => 'Reset token is required',
                    'min_length' => 'Invalid reset token',
                    'max_length' => 'Invalid reset token'
                ]
            ],
            'password' => [
                'label' => 'New Password',
                'rules' => 'required|min_length[8]|max_length[255]',
                'errors' => [
                    'required' => 'Password is required',
                    'min_length' => 'Password must be at least 8 characters',
                    'max_length' => 'Password cannot exceed 255 characters'
                ]
            ],
            'password_confirm' => [
                'label' => 'Confirm Password',
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Please confirm your password',
                    'matches' => 'Password confirmation does not match'
                ]
            ]
        ];
    }

    /**
     * Admin user creation rules
     */
    public static function getAdminCreateUserRules(): array
    {
        $rules = static::getRegistrationRules();

        // Add admin-specific fields
        $rules['role'] = [
            'label' => 'Role',
            'rules' => 'required|in_list[admin,technician,customer]',
            'errors' => [
                'required' => 'Role is required',
                'in_list' => 'Invalid role selected'
            ]
        ];

        $rules['is_active'] = [
            'label' => 'Status',
            'rules' => 'required|in_list[0,1]',
            'errors' => [
                'required' => 'Status is required',
                'in_list' => 'Invalid status'
            ]
        ];

        return $rules;
    }

    /**
     * Bulk validation untuk import users
     */
    public static function getBulkImportRules(): array
    {
        return [
            'file' => [
                'label' => 'Import File',
                'rules' => 'uploaded[file]|ext_in[file,csv,xlsx]|max_size[file,2048]',
                'errors' => [
                    'uploaded' => 'Please select a file to upload',
                    'ext_in' => 'File must be CSV or Excel format',
                    'max_size' => 'File size cannot exceed 2MB'
                ]
            ]
        ];
    }
}