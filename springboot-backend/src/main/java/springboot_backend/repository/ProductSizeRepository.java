package springboot_backend.repository;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;
import springboot_backend.entity.Product;
import springboot_backend.entity.ProductSize;
import springboot_backend.entity.Size;

import java.util.List;
import java.util.Optional;

@Repository
public interface ProductSizeRepository extends JpaRepository<ProductSize, Long> {
    List<ProductSize> findByProduct(Product product);
    List<ProductSize> findByProductId(Long productId);
    Optional<ProductSize> findByProductAndSize(Product product, Size size);
    Optional<ProductSize> findByProductIdAndSizeId(Long productId, Long sizeId);
    void deleteByProductId(Long productId);
}

