# Requirements Document

## Introduction

Dokumen ini mendefinisikan requirements untuk mengidentifikasi dan mengeliminasi kode repetitive di seluruh project LMS, serta standardisasi mekanisme filtering dan sorting pada API endpoints. Tujuannya adalah meningkatkan reusability, maintainability, dan konsistensi dalam penanganan query parameters untuk filtering dan sorting data.

## Glossary

- **Repetitive Code**: Kode yang muncul berulang di berbagai lokasi dengan pola yang sama atau sangat mirip
- **Reusable Function**: Function yang dapat digunakan kembali di berbagai konteks tanpa duplikasi
- **Filter**: Mekanisme untuk menyaring data berdasarkan kriteria tertentu melalui query parameters
- **Sort**: Mekanisme untuk mengurutkan data berdasarkan field tertentu dengan arah ascending atau descending
- **Allowed Filters**: Daftar field yang diizinkan untuk digunakan sebagai filter pada endpoint tertentu
- **Allowed Sort Fields**: Daftar field yang diizinkan untuk digunakan sebagai sort key pada endpoint tertentu
- **QueryFilter**: Class atau trait yang menangani parsing dan aplikasi filter/sort dari request
- **FilterableRepository**: Repository yang mendukung filtering dan sorting dengan whitelist

## Requirements

### Requirement 1

**User Story:** As a developer, I want to identify all repetitive code patterns across the project, so that I can consolidate them into reusable functions.

#### Acceptance Criteria

1. WHEN analyzing the codebase THEN the System SHALL identify code blocks that appear in 3 or more locations with similar logic
2. WHEN repetitive validation logic is found THEN the Logic SHALL be extracted to shared validation rules or FormRequest classes
3. WHEN repetitive data transformation logic is found THEN the Logic SHALL be extracted to DTO methods or helper functions
4. WHEN repetitive query patterns are found THEN the Patterns SHALL be extracted to Repository methods or Query Scopes
5. WHEN repetitive authorization checks are found THEN the Checks SHALL be extracted to Policies or shared Traits

### Requirement 2

**User Story:** As a developer, I want a centralized filtering mechanism, so that all API endpoints handle filters consistently.

#### Acceptance Criteria

1. WHEN an API endpoint supports filtering THEN the Endpoint SHALL define allowed filter fields explicitly in a whitelist
2. WHEN a filter parameter is received THEN the System SHALL validate it against the allowed filters list
3. WHEN an invalid filter field is used THEN the System SHALL reject the request with 400 status and clear error message
4. WHEN applying filters THEN the Repository SHALL use the QueryFilter mechanism to build queries safely
5. WHEN no filters are provided THEN the System SHALL return all data (subject to pagination)

### Requirement 3

**User Story:** As a developer, I want a centralized sorting mechanism, so that all API endpoints handle sorting consistently.

#### Acceptance Criteria

1. WHEN an API endpoint supports sorting THEN the Endpoint SHALL define allowed sort fields explicitly in a whitelist
2. WHEN a sort parameter is received THEN the System SHALL validate it against the allowed sort fields list
3. WHEN an invalid sort field is used THEN the System SHALL reject the request with 400 status and clear error message
4. WHEN sort direction is specified THEN the System SHALL accept only 'asc' or 'desc' values
5. WHEN no sort is provided THEN the System SHALL apply a default sort order defined by the endpoint

### Requirement 4

**User Story:** As a developer, I want filter and sort configurations to be documented, so that frontend developers know what parameters are available.

#### Acceptance Criteria

1. WHEN an endpoint supports filtering THEN the API documentation SHALL list all allowed filter fields with their data types
2. WHEN an endpoint supports sorting THEN the API documentation SHALL list all allowed sort fields
3. WHEN filter operators are supported THEN the Documentation SHALL specify available operators (eq, like, gt, lt, in, between)
4. WHEN complex filters are supported THEN the Documentation SHALL provide examples of filter syntax
5. WHEN documentation is generated THEN the System SHALL automatically extract allowed filters and sorts from endpoint configurations
