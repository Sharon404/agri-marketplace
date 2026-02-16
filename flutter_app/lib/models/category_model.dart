class CategoryModel {
  CategoryModel({
    required this.id,
    required this.name,
    required this.slug,
    this.parentId,
  });

  final int id;
  final String name;
  final String slug;
  final int? parentId;

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      parentId: json['parent_id'] as int?,
    );
  }
}
