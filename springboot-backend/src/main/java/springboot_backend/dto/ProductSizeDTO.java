package springboot_backend.dto;

public class ProductSizeDTO {
    private Long id;
    private Long sizeId;
    private String name;
    private String displayName;
    private Integer sortOrder;
    private Integer stockQuantity;

    public ProductSizeDTO() {
    }

    public ProductSizeDTO(Long id, Long sizeId, String name, String displayName, Integer sortOrder, Integer stockQuantity) {
        this.id = id;
        this.sizeId = sizeId;
        this.name = name;
        this.displayName = displayName;
        this.sortOrder = sortOrder;
        this.stockQuantity = stockQuantity;
    }

    public Long getId() {
        return id;
    }

    public void setId(Long id) {
        this.id = id;
    }

    public Long getSizeId() {
        return sizeId;
    }

    public void setSizeId(Long sizeId) {
        this.sizeId = sizeId;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getDisplayName() {
        return displayName;
    }

    public void setDisplayName(String displayName) {
        this.displayName = displayName;
    }

    public Integer getSortOrder() {
        return sortOrder;
    }

    public void setSortOrder(Integer sortOrder) {
        this.sortOrder = sortOrder;
    }

    public Integer getStockQuantity() {
        return stockQuantity;
    }

    public void setStockQuantity(Integer stockQuantity) {
        this.stockQuantity = stockQuantity;
    }
}

