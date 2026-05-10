# Styles

## Model
- Table: styles
- Belongs to: User (user_id)
- Soft Deletes: yes/no

## Fields
| Field | Type | Required | Rules | Label |
|---|---|---|---|---|
| name | string | yes | max:255 | Name |
| description | text | yes |  | Description |

## Permissions
- View: styles:view
- Manage: styles:manage

## Menu
- Label: Styles
- Icon: palette
- Order: menu

## Table Columns (Index)
| Column | Type | Sortable |
|---|---|---|
| id | integer | yes |
| created_at | timestamp | yes |
| updated_at | timestamp | yes |
| deleted_at | timestamp | no |
| user_id | string | no |
| name | string | yes |
| desription | text | no |

## Searchable Fields
- name