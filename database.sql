
CREATE DATABASE IF NOT EXISTS education_system;
USE education_system;

-- جدول الأقسام في وزارة التعليم العالي
CREATE TABLE departments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(