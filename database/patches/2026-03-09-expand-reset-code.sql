-- Expand reset token storage for secure password reset flow.
ALTER TABLE users
  MODIFY COLUMN reset_code VARCHAR(255) NULL;

-- Expand activation token storage for secure account activation links.
ALTER TABLE users
  MODIFY COLUMN activate_code VARCHAR(255) NULL;
