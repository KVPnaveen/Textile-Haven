package springboot_backend.dto;

import com.fasterxml.jackson.annotation.JsonAlias;

public class AddToCartRequest {
    @JsonAlias({"product_id", "productId"})
    private Long productId;

    @JsonAlias({"size_id", "sizeId"})
    private Long sizeId;

    private Integer quantity = 1;

    public AddToCartRequest() {
    }

    public AddToCartRequest(Long productId, Long sizeId, Integer quantity) {
        this.productId = productId;
        this.sizeId = sizeId;
        this.quantity = quantity;
    }

    public Long getProductId() {
        return productId;
    }

    public void setProductId(Long productId) {
        this.productId = productId;
    }

    public Long getSizeId() {
        return sizeId;
    }

    public void setSizeId(Long sizeId) {
        this.sizeId = sizeId;
    }

    public Integer getQuantity() {
        return quantity;
    }

    public void setQuantity(Integer quantity) {
        this.quantity = quantity;
    }
}

